<?php

namespace backend\services;


use backend\models\Artist;
use backend\models\Invoice;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yii;
use Mpdf\Mpdf;


class ArtistPayoutReportService
{
    /**
     *
     * public function actionExportExcel(int $invoiceId, int $artistId)
     * {
     * $service = new ArtistPayoutExcelExportService();
     * $file = $service->export($invoiceId, $artistId);
     *
     * return Yii::$app->response->sendFile(
     * $file,
     * "artist_payout_{$invoiceId}.xlsx"
     * );
     * }
     * @param int $payoutInvoiceId
     * @param int $artistId
     * @return string
     */
    public function export(int $payoutInvoiceId, int $artistId): string
    {
        $coverage = $this->loadCoverageData($payoutInvoiceId, $artistId);
        $details = $this->loadDetails($coverage);
        
        $rows = $this->buildRows($coverage, $details);
        
        return $this->buildSpreadsheet($rows);
    }
    
    /**
     * Генерує PDF звіт артиста по конкретній виплаті
     *
     * $service = new ArtistPayoutReportService();
     * $pdfBinary = $service->generatePdf($payoutInvoiceId, $artistId);
     *
     * file_put_contents('/tmp/payout_report.pdf', $pdfBinary);
     */
    public function generatePdf(int $payoutInvoiceId, int $artistId): string
    {
        // 1️⃣ Фаза 1 — фінансовий coverage (швидко)
        $coverageRows = $this->loadCoverageData($payoutInvoiceId, $artistId);
        
        if (empty($coverageRows)) {
            throw new \RuntimeException('No coverage data found');
        }
        
        // 2️⃣ Фаза 2 — деталізація по треках
        $details = $this->loadDetails($coverageRows);
        
        // 3️⃣ Готуємо view‑model
        $viewData = $this->buildViewModel($coverageRows, $details);
        
        // 4️⃣ Рендер PDF
        return $this->renderPdf($viewData);
    }
    
    
    public function getCoverageSummary(int $payoutInvoiceId, int $artistId): array
    {
        $rows = Yii::$app->db->createCommand("
        SELECT
            income_year,
            income_quarter,
            SUM(covered_amount) AS total
        FROM v_payout_income_invoice_coverage
        WHERE payout_invoice_id = :pid
          AND artist_id = :aid
        GROUP BY income_year, income_quarter
        ORDER BY income_year, income_quarter
        ")->bindValues([
            ':pid' => $payoutInvoiceId,
            ':aid' => $artistId
        ])->queryAll();
        
        return $rows;
    }
    
    
    public function calculateAmounts(float $gross, Artist $artist): array
    {
        $gross = abs(round($gross, 2));
        
        if ($artist->artist_type_id == 1) {
            $pdv = round($gross * 0.18, 2);
            $vzb = round($gross * 0.05, 2);
            $net = $gross - $pdv - $vzb;
        } else {
            $pdv = 0;
            $vzb = 0;
            $net = $gross;
        }
        
        return compact('gross', 'pdv', 'vzb', 'net');
    }
    
    
    public function generateActPdf(int $payoutInvoiceId, int $artistId): string
    {
        $invoice = Invoice::findOne($payoutInvoiceId);
        $artist  = Artist::findOne($artistId);
        
        $coverage = $this->getCoverageSummary($payoutInvoiceId, $artistId);
        
        $amount = array_sum(array_column($coverage, 'total'));
        $calc   = $this->calculateAmounts($amount, $artist);
        
        $data = [
            'invoice_id'   => $invoice->invoice_id,
            'date_pay'     => $invoice->date_pay,
            'artist'       => $artist,
            'quarterDate'  => $coverage, // ✅ тепер МОЖЕ БУТИ ДЕКІЛЬКА КВАРТАЛІВ
            'amount'       => $calc['gross'],
            'pdv'          => $calc['pdv'],
            'v_zbir'       => $calc['vzb'],
            'total'        => $calc['net'],
        ];
        
        $content = Yii::$app->controller->renderPartial(
            'pdf/act',
            $data
        );
        
        $pdf = new \kartik\mpdf\Pdf([
            'mode' => \kartik\mpdf\Pdf::MODE_UTF8,
            'format' => \kartik\mpdf\Pdf::FORMAT_A4,
            'destination' => \kartik\mpdf\Pdf::DEST_FILE,
            'content' => $content,
            'cssFile' => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.css',
            'methods' => [
                'SetFooter' => ['{PAGENO}/{nb}'],
            ],
        ]);
        
        $filename = Yii::getAlias('@backend/pdf/')
            . $artist->id . "_act_invoice_{$invoice->invoice_id}.pdf";
        
        $pdf->filename = $filename;
        $pdf->render();
        
        return $filename;
    }
    
    
    
    
    /**
     * ФАЗА 1 — що саме увійшло у виплату
     */
    private function loadCoverageData(int $payoutInvoiceId, int $artistId): array
    {
        $sql = "
            SELECT
                payout_invoice_id,
                income_invoice_id,
                income_item_id,
                income_year,
                income_quarter,
                aggregator_report_id,
                covered_amount
            FROM v_payout_income_invoice_coverage
            WHERE payout_invoice_id = :payout_invoice_id
              AND artist_id = :artist_id
        ";
        
        return Yii::$app->db->createCommand($sql)->bindValues([
            ':payout_invoice_id' => $payoutInvoiceId,
            ':artist_id' => $artistId,
        ])->queryAll();
    }
    
    /**
     * ФАЗА 2 — деталізація (керована)
     */
    private function loadDetails(array $coverageRows): array
    {
        $result = [];
        
        $sql = "
            SELECT
                ari.track_id,
                t.name AS track_name,
                ari.isrc,
                ari.platform,
                ari.country,
                ari.date_report,
                ari.count AS streams_count,
                ari.amount AS report_amount
            FROM aggregator_report_item ari
            JOIN track t ON t.id = ari.track_id
            WHERE ari.report_id = :report_id
              AND ari.track_id = (
                    SELECT track_id FROM invoice_items WHERE id = :income_item_id
              )
            ORDER BY ari.date_report
        ";
        
        foreach ($coverageRows as $row) {
            $rows = Yii::$app->db->createCommand($sql)->bindValues([
                ':report_id' => $row['aggregator_report_id'],
                ':income_item_id' => $row['income_item_id'],
            ])->queryAll();
            
            $result[$row['income_item_id']] = $rows;
        }
        
        return $result;
    }
    
    /**
     * Збираємо дані в структуру для PDF
     */
    private function buildViewModel(array $coverageRows, array $details): array
    {
        $data = [];
        
        foreach ($coverageRows as $row) {
            $key = $row['income_year'] . 'Q' . $row['income_quarter'];
            
            if (!isset($data[$key])) {
                $data[$key] = [
                    'year' => $row['income_year'],
                    'quarter' => $row['income_quarter'],
                    'total' => 0,
                    'items' => [],
                ];
            }
            
            $data[$key]['total'] += $row['covered_amount'];
            
            $data[$key]['items'][] = [
                'income_item_id' => $row['income_item_id'],
                'covered_amount' => $row['covered_amount'],
                'tracks' => $details[$row['income_item_id']] ?? [],
            ];
        }
        
        return $data;
    }
    
    /**
     * Генерація PDF
     */
    private function renderPdf(array $viewData): string
    {
        $mpdf = new Mpdf([
            'format' => 'A4',
            'margin_top' => 40,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
        ]);


// 🔹 Variables
        $headerHtml = str_replace(
            ['{{logo_path}}', '{{artist_name}}', '{{invoice_id}}', '{{report_date}}'],
            [
                Yii::getAlias('@frontend/img/blackbeats.png'),
                $viewData['artist_name'] ?? '',
                $viewData['invoice_id'] ?? '',
                date('Y-m-d'),
            ],
            file_get_contents(__DIR__ . '/pdf/header.html')
        );
        
        $footerHtml = str_replace(
            ['{{company_name}}', '{{generated_at}}'],
            ['Black Beats Music', date('Y-m-d H:i')],
            file_get_contents(__DIR__ . '/pdf/footer.html')
        );
        
        $mpdf->SetHTMLHeader($headerHtml);
        $mpdf->SetHTMLFooter($footerHtml);
        
        
        // 🔹 Content
        $html = '';
        
        foreach ($viewData['periods'] as $period) {
            $html .= "<h2>{$period['year']} Q{$period['quarter']}</h2>";
            $html .= "<p><strong>Total covered:</strong> {$period['total']}</p>";
            
            $html .= "
        <table width='100%' border='1' cellspacing='0' cellpadding='5'>
            <thead>
            <tr style='background:#f5f5f5'>
                <th>Track</th>
                <th>Platform</th>
                <th>Country</th>
                <th>Streams</th>
                <th>Amount Paid</th>
            </tr>
            </thead>
            <tbody>
        ";
            
            foreach ($period['items'] as $item) {
                foreach ($item['tracks'] as $track) {
                    $html .= "<tr>
                    <td>{$track['track_name']}</td>
                    <td>{$track['platform']}</td>
                    <td>{$track['country']}</td>
                    <td>{$track['streams_count']}</td>
                    <td>{$track['amount_paid']}</td>
                </tr>";
                }
            }
            
            $html .= "</tbody></table><br>";
        }
        
        $mpdf->WriteHTML($html);
        
        return $mpdf->Output('', 'S');
        
    }
    
    /* -------------------------------------------------------------
     * Формуємо плоскі рядки для Excel
     * ------------------------------------------------------------- */
    private function buildRows(array $coverage, array $details): array
    {
        $rows = [];
        
        foreach ($coverage as $row) {
            foreach ($details[$row['income_item_id']] ?? [] as $track) {
                $rows[] = [
                    'year' => $row['income_year'],
                    'quarter' => 'Q' . $row['income_quarter'],
                    'track' => $track['track_name'],
                    'isrc' => $track['isrc'],
                    'platform' => $track['platform'],
                    'country' => $track['country'],
                    'date' => $track['date_report'],
                    'streams' => $track['streams'],
                    'gross' => $track['report_amount'],
                    'covered' => $row['covered_amount'],
                ];
            }
        }
        
        return $rows;
    }
    
    /* -------------------------------------------------------------
     * Генерація Excel
     * ------------------------------------------------------------- */
    private function buildSpreadsheet(array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Header
        $headers = [
            'Year',
            'Quarter',
            'Track',
            'ISRC',
            'Platform',
            'Country',
            'Report Date',
            'Streams',
            'Gross Amount',
            'Covered in Payout',
        ];
        
        $sheet->fromArray($headers, null, 'A1');
        
        // Data
        $sheet->fromArray($rows, null, 'A2');
        
        // Auto width
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $tmpFile = tempnam(sys_get_temp_dir(), 'payout_excel_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tmpFile);
        
        return $tmpFile;
    }
}
