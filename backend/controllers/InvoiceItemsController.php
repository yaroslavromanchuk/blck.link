<?php

namespace backend\controllers;

use backend\models\Artist;
use backend\models\ArtistLogType;
use backend\models\Invoice;
use backend\models\InvoiceLog;
use backend\models\InvoiceLogType;
use backend\models\InvoiceStatus;
use backend\models\InvoiceType;
use backend\models\Track;
use backend\models\User;
use backend\widgets\DateFormat;
use backend\widgets\Str;
use common\models\Mail;
use common\models\MailLog;
use common\models\t;
use kartik\mpdf\Pdf;
use PhpOffice\PhpSpreadsheet\Reader\Html;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Yii;
use backend\models\InvoiceItems;
use backend\models\InvoiceItemsSearch;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * InvoiceItemsController implements the CRUD actions for InvoiceItems model.
 */
class InvoiceItemsController extends Controller
{
    private static string $homePage = '/home/atpjwxlx/domains/blck.link/public_html/backend/web/';
    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all InvoiceItems models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new InvoiceItemsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single InvoiceItems model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new InvoiceItems model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate(int $id)
    {
        $model = new InvoiceItems();

        if (!$model->load(Yii::$app->request->post())) {
            $errors = $model->getErrors();
            Yii::$app->session->setFlash('error', current($errors));

            return $this->redirect(['invoice/view', 'id' => $id]);
        }

        if (in_array($model->invoice->invoice_type, [3, 4]) && $model->amount > 0) { // 3- витрати, 4 - баланс
            $model->amount = $model->amount * -1;
        }

        if ($model->invoice->invoice_type == 2 && $model->amount == 0) { // 2- виплата
            $artist = Artist::findOne($model->artist_id);
            if (!is_null($artist)) {
                if ($model->invoice->currency_id == 1) {
                    $model->amount = $artist->deposit_1 * -1;
                } else {
                    $model->amount = $artist->deposit * -1;
                }
            }
        }

        if ($model->track_id && !$model->isrc) {
            $model->isrc = Track::findOne($model->track_id)->isrc;
        }

        if (!$model->save()) {
            $errors = $model->getErrors();
            Yii::$app->session->setFlash('error', current($errors));

            return $this->redirect(['invoice/view', 'id' => $id]);
        }

        $model->invoice->calculate();

        return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
    }

    /**
     * Updates an existing InvoiceItems model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing InvoiceItems model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id, $url = '')
    {
        $model = $this->findModel($id);

        if ($model->invoice->invoice_status_id == InvoiceStatus::Calculated) {
            Yii::$app->session->setFlash('error', 'Неможна видаляти записи з інвойсу в статусі Проведений.');

            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
                return ['success' => false];
            }

            if (!empty($url)) {
                return $this->redirect($url);

            }
        }

        if ($model->delete() !== false) {
            $model->invoice->calculate();

            $db = Yii::$app->db;
            $db->createCommand(
                "UPDATE aggregator_report_item ari 
                            INNER JOIN track t ON REPLACE(t.isrc, '-', '') = REPLACE(ari.isrc, '-', '') and t.artist_id = {$model->artist_id}
                        SET ari.payment_invoice_id = null
                        WHERE ari.payment_invoice_id = {$model->invoice_id}"
            )->execute();

            $db->createCommand(
                "UPDATE `invoice_items` ii 
                        SET ii.`payment_invoice_id`= null
                         WHERE ii.`payment_invoice_id`= {$model->invoice_id}
                         AND ii.`artist_id`= {$model->artist_id}"
            )->execute();

            if ($model->invoice->invoice_type == InvoiceType::$credit
                && in_array($model->invoice->invoice_status_id, [2, 4]) // проведений або в процесі виплати
            ) {
                Artist::calculationDeposit($model->artist_id);
            }
        }

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return ['success' => true];
        }


        if (!empty($url)) {
            return $this->redirect($url);
        }

        return $this->redirect(['index']);
    }

    public function actionPdfBalance(int $id, $redirect = true)
    {
        $model = $this->findModel($id);

        $date = new \DateTime($model->invoice->date_pay);
        $name = Str::transliterate($model->artist->name);
        $filename = $date->format('Y_m_d') . "_{$name}_balance_q{$model->invoice->quarter}_invoice_{$model->invoice->invoice_id}.pdf";

        if (file_exists(self::$homePage . 'pdf/' . $filename)) {
            $this->redirect("/pdf/".$filename);
        }

        $this->layout = 'pdf';
        $vutraty = $this->getVutraty($model->artist, $model->invoice->currency_id, $model->invoice);


        $content = $this->render(
            'pdf/balance/view',
            [
                'model' => $model,
                'all' => Artist::getLog(
                    $model->artist_id,
                    $model->invoice->quarter,
                    $model->invoice->year,
                    $model->invoice->currency_id,
                    $model->invoice->currency->currency_name,
                    $model->invoice_id
                ),
                'costs' => $vutraty,
            ]
        );

      //  return $content;

        $pdf = new Pdf(config: [
            // set to use core fonts only
            'mode' => Pdf::MODE_UTF8,
            // A4 papr format
            'format' => Pdf::FORMAT_A4,
            // portrait orientation
            'orientation' => Pdf::ORIENT_PORTRAIT,
            // stream to browser inline
            // 'destination' => Pdf::DEST_BROWSER, // відкрити в браузері без зберігання
            'destination' => Pdf::DEST_FILE, // зберегти в файл, на майбутнє для відправки файлу поштою
            'marginLeft' => 5,
            'marginTop' => 5,
            'marginRight' => 5,
            'marginHeader' => 5,
            'defaultFont' => 'Times New Roman", Times, serif',
            // your html content input
            'content' => $content,
            // format content from your own css file if needed or use the
            // enhanced bootstrap css built by Krajee for mPDF formatting
             'cssFile' => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.css',
            // any css to be embedded if required
           // 'cssInline' => '.city{float:left, color:red}',
            // set mPDF properties on the fly
            'options' => [
                'title' => 'Balance Sheet',
            ],
            // call mPDF methods on the fly
            'methods' => [
                //'SetHeader' => ['BLACKBEATS'],
               // 'SetFooter' => ['{PAGENO}/{nb}'],
            ],
        ]);

        // $pdf->filename = "Invoice_{$model->invoice_id}_artist_{$model->artist_id}.pdf";
        $pdf->filename = self::$homePage . 'pdf/' . $filename;
        // return the pdf output as per the destination setting
        $pdf->render();

        if ($redirect) {
            $this->redirect("/pdf/".$filename);
        }
    }

    public function actionExportBalance(int $id)
    {
        $model = $this->findModel($id);
        $date = new \DateTime($model->invoice->date_pay);
        $name = Str::transliterate($model->artist->name);
        $filename = $date->format('Y_m_d') . "_{$name}_balance_q{$model->invoice->quarter}_invoice_{$model->invoice->invoice_id}.xlsx";

        if (file_exists(self::$homePage . 'xls/' .$filename)) {
            $this->redirect("/xls/".$filename);
        }

        $this->layout = 'pdf';

        $vutraty = $this->getVutraty($model->artist, $model->invoice->currency_id, $model->invoice);

        $content = $this->render(
            'pdf/balance/view',
            [
                'model' => $model,
                'all' => Artist::getLog(
                    $model->artist_id,
                    $model->invoice->quarter,
                    $model->invoice->year,
                    $model->invoice->currency_id,
                    $model->invoice->currency->currency_name,
                    $model->invoice_id
                ),
                'costs' => $vutraty,
            ]
        );

        $reader = new Html();
        $writer = new Xlsx($reader->loadFromString($content));
        $writer->save(self::$homePage . 'xls/' . $filename);

        $this->redirect("/xls/".$filename);
    }

    public function actionPdfAct(int $id, $redirect = true)
    {
        $model = $this->findModel($id);
        $date = new \DateTime($model->invoice->date_pay);
        $name = Str::transliterate($model->artist->name);
        $filename = $date->format('Y_m_d') . "_{$name}_act_q{$model->invoice->quarter}_invoice_{$model->invoice->invoice_id}.pdf";

        if (file_exists(self::$homePage . 'pdf/' . $filename) && $redirect) {
           $this->redirect("/pdf/".$filename);
        }

        // перевірка чи всі дані заповнені
        if($this->checkBeforeExport($model) !== true) {
        Yii::$app->session->setFlash('error', 'У артиста не заповнені всі дані');
            return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
        }

        $this->layout = 'pdf';

        $tracks = $this->getReportData($model->invoice_id, $model->artist_id, true);

        $feats = $this->getReportDataFeat($model->invoice_id, $model->artist_id, true);

        ///$quarterDate = $model->artist->getLastPayInvoice($model->invoice_id);

        $quarterDate = DateFormat::getQuarterDate($model->invoice->quarter, $model->invoice->year);
        $quarterDate['start'] = date('d.m.Y', strtotime($model->invoice->period_from));
        $quarterDate['end'] = date('d.m.Y', strtotime($model->invoice->period_to));


      /*  echo '<pre>';
        print_r($tracks);
        print_r($feats);
        echo '</pre>';
        exit;*/
        $content = $this->render(
            'pdf/act',
            [
                'model' => $model,
                'tracks' => $tracks,
                'feats' => $feats,
                'quarterDate' => $quarterDate
            ]
        );

       //return $content;

        $pdf = new Pdf(config: [
            // set to use core fonts only
            'mode' => Pdf::MODE_UTF8,
            // A4 papr format
            'format' => Pdf::FORMAT_A4,
            // portrait orientation
            'orientation' => Pdf::ORIENT_PORTRAIT,
            // stream to browser inline
           // 'destination' => Pdf::DEST_BROWSER, // відкрити в браузері без зберігання
            'destination' => Pdf::DEST_FILE, // зберегти в файл, на майбутнє для відправки файлу поштою
            'marginLeft' => 5,
            'marginTop' => 5,
            'marginRight' => 5,
            'marginHeader' => 5,
            'defaultFont' => 'Times New Roman", Times, serif',
            // your html content input
            'content' => $content,
            // format content from your own css file if needed or use the
            // enhanced bootstrap css built by Krajee for mPDF formatting
            'cssFile' => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.css',
            // any css to be embedded if required
            'cssInline' => '.city{float:left, color:red}',
            // set mPDF properties on the fly
            'options' => [
                'title' => 'Invoice'
            ],
            // call mPDF methods on the fly
            'methods' => [
               //'SetHeader' => ['BLACKBEATS'],
                'SetFooter' => ['{PAGENO}/{nb}'],
                //'SetWatermarkImage' => ['/img/blackbeats_ws.png'],
                //'SetHTMLHeader' => '<div style="position: fixed; top:-35px; right: 0px"><img src="/img/blackbeats_ws.png" width="75px"  alt="{BLACKBEATS}" /></div>'
            ],
        ]);

       // $pdf->filename = "Invoice_{$model->invoice_id}_artist_{$model->artist_id}.pdf";
        $pdf->filename = self::$homePage . 'pdf/' . $filename;
        // return the pdf output as per the destination setting
        $pdf->render();

        if ($redirect) {
            $this->redirect("/pdf/".$filename);
        }
    }

    public function actionExportAct(int $id, bool $redirect = true, array $invoiceItemsIds = [])
    {
        $model = $this->findModel($id);

        $invoiceItemsIds = !empty($invoiceItemsIds) ? $invoiceItemsIds : $this->getAllInvoiceItemsInProgressForArtist($model);

        //$date = new \DateTime($model->invoice->date_pay);
       // $name = Str::transliterate($model->artist->name);
        $name = Str::transliterate($model->artist->name) . "_" . implode('_', $invoiceItemsIds['invoice']);
      //  $filename = $date->format('Y_m_d') . "_{$name}_act_q{$model->invoice->quarter}_invoice_{$model->invoice->invoice_id}.xlsx";

        $filename = "report_{$name}_q{$model->invoice->quarter}_year_{$model->invoice->year}.xlsx";

        if (file_exists(self::$homePage . 'xls/' .$filename)) {
            if ($redirect === false) {
                return;
            }

            $this->redirect("/xls/".$filename);
        }

        // перевірка чи всі дані заповнені
        if($this->checkBeforeExport($model) !== true) {
            return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
        }

        $tempData1 = [];
        $tempData1[] = ['Звіт за ' . $model->invoice->quarter . ' кв. ' . $model->invoice->year . 'р., ' . $model->artist->name];
        $tempData2 = [];
        $tempData2[] = [
            '№',
            'Виконавець',
            'Назва Твору',
            'Кіл-ть Використань',
            'Частка авторських (суміжних) прав, %',
            'Загальна сума отриманої Винагороди Видавцем',
            'Ставка Винагороди Правовласника за авторські та суміжні права, %',
            'Сума Роялті правовласника',
            'Валюта',
            'Вид прав',
            'Тип використання',
            'Тип та/або ресурс використання',
            'Країна',
            'Період використання Об\'єкта',
        ];


        $i = 1;
        $j = 1;
        $sum = ['USD'=>0, 'EUR'=>0, 'UAH'=>0];
        $sum2 = ['USD'=>0, 'EUR'=>0, 'UAH'=>0];
        $balances = [];
        $costs = [];

        foreach ($invoiceItemsIds['items'] as $id) {
            $_model = $id == $model->id ? $model : $this->findModel($id);

            $balance = Artist::getLog(
                $_model->artist_id,
                $_model->invoice->quarter,
                $_model->invoice->year,
                $_model->invoice->currency_id,
                $_model->invoice->currency->currency_name,
                $_model->invoice_id
            );

            $balance[] = [
                'Операція',
                'Сума',
                'Валюта'
            ];
            foreach ($balance as $b) {

                // if name have <b> or <i> set style
                $balances[] = [
                     $b['name'],
                    number_format(round($b['value'], 2), 2, '.', ''),
                     $b['currency_name'],
                ];
                $i++;
                $balance[] = [];
                $i++;
            }

            $cost = $this->getVutraty($_model->artist, $_model->invoice->currency_id, $_model->invoice);
            $costs[] = [
                'Дата',
                'Тип',
                'Виконавець',
                'Трек',
                'Стаття витрат',
                'Сума',
                'Валюта',
            ];
            foreach ($cost as $c) {
                $costs[] = [
                    $c['date_item'],
                    $c['invoice_type_name'],
                    $c['a_name'],
                    rtrim($c['t_name'], '1'),
                    $c['description'],
                    number_format(round($c['amount'], 2), 2, '.', ''),
                    $c['currency_name'],
                ];
            }

            $tracks = $this->getReportData($_model->invoice_id, $_model->artist_id, false);

            if (!empty($tracks)) {
                foreach ($tracks as $item) {
                    if ($item['amount'] > 0) {
                        $amount = round($item['amount'] * ($item['percentage_label'] /100), 4);
                        $tempData2[] = [
                            $i,
                            $item['artist_name'],
                            rtrim($item['track_name'], '1'),
                            $item['count'],
                            $item['percentage'],
                            round($item['amount'], 4),
                            $item['percentage_label'],
                            $amount,
                            $item['currency_name'],
                            $item['prav1'],
                            $item['prav2'],
                            $item['platform'],
                            $item['country'],
                            DateFormat::datumUah2($item['date_report']),
                        ];
                        $j++;
                        $sum2[$item['currency_name']] += $amount;
                    }
                }
            }

            $feats = $this->getReportDataFeat($_model->invoice_id, $_model->artist_id);

            if (!empty($feats)) {
                foreach ($feats as $item) {
                    if ($item['amount'] > 0) {
                        $amount = round($item['amount'] * ($item['percentage_label'] /100), 4);
                        $tempData2[] = [
                            $i,
                            $item['artist_name'] . '( ' . $item['feat_name'] . ')',
                            rtrim($item['track_name'], '1'),
                            $item['count'],
                            $item['percentage'],
                            round($item['amount'], 4),
                            $item['percentage_label'],
                            $amount,
                            $item['currency_name'],
                            $item['prav1'],
                            $item['prav2'],
                            $item['platform'],
                            $item['country'],
                            DateFormat::datumUah2($item['date_report']),
                        ];
                        $j++;
                        $sum2[$item['currency_name']] += $amount;
                    }
                }
            }
        }

        $spreadSheet = new Spreadsheet();
        // баланси
        $workSheet = $spreadSheet->getActiveSheet();
        $workSheet->setTitle('Баланс');


        $workSheet->getStyle('A1:K1')->getFont()->setBold(true);
        //$workSheet->getStyle('A2:Q2')->getFont()->setBold(true);

        $workSheet->getColumnDimension('A')->setWidth(40);
        $workSheet->getColumnDimension('B')->setWidth(10);
        $workSheet->getColumnDimension('C')->setWidth(10);
        $workSheet->mergeCells('A1:C1');
        $workSheet->getStyle('A1:C1')->getFont()->setBold(true);

        $tempData1 = array_merge($tempData1, $balances);

        if (!empty($costs)) {
            if ($i > 1) {
                ++$i;
                ++$i;
            }

            $workSheet->mergeCells("A{$i}:G{$i}");
            ++$i;
            $workSheet->getStyle("A{$i}:G{$i}")->getFont()->setBold(true);

            $tempData1[] = [];
            $tempData1[] = ['Витрати'];

            $tempData1 = array_merge($tempData1, $costs);
        }

        // зберегти баланс на першому аркуші
        $workSheet->fromArray($tempData1);

        // запис звіту
        $spreadSheet->createSheet();
        $spreadSheet->setActiveSheetIndex(1);
        $workSheet = $spreadSheet->getActiveSheet();
        $workSheet->setTitle('Звіт');

        $workSheet->getStyle('A1:N1')->getAlignment()->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::HORIZONTAL_CENTER);

        $workSheet->getColumnDimension('A')->setWidth(8);
        $workSheet->getColumnDimension('B')->setWidth(12);
        $workSheet->getColumnDimension('C')->setWidth(12);
        $workSheet->getColumnDimension('D')->setWidth(13);
        $workSheet->getColumnDimension('E')->setWidth(11);
        $workSheet->getColumnDimension('F')->setWidth(12);
        $workSheet->getColumnDimension('G')->setWidth(15);
        $workSheet->getColumnDimension('H')->setWidth(15);
        $workSheet->getColumnDimension('I')->setWidth(8);
        $workSheet->getColumnDimension('J')->setWidth(11);
        $workSheet->getColumnDimension('K')->setWidth(14);
        $workSheet->getColumnDimension('L')->setWidth(14);
        $workSheet->getColumnDimension('M')->setWidth(15);
        $workSheet->getColumnDimension('N')->setWidth(15);

        $workSheet->getStyle('A1:N1')->getFont()->setBold(true);
        $workSheet->getRowDimension('1')->setRowHeight(100);

        $workSheet->fromArray($tempData2);

        $q = $j+2;
        $workSheet->setCellValue('A' . $q, 'Всього:');
        $workSheet->getStyle('A'. $q)->getFont()->setBold(true);

        foreach ($sum2 as $key => $item) {
            $temp = ++$q;
            $workSheet->setCellValue('A' . $temp, $key);
            $workSheet->setCellValue('B' . $temp, round($item, 2));
            $workSheet->getStyle('A'. $temp)->getFont()->setBold(true);
            $workSheet->getStyle('B'. $temp)->getFont()->setBold(true);
        }

        // переключитись на 1 аркуш
        $spreadSheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadSheet);
        $writer->save(self::$homePage . 'xls/' . $filename);

       // $reader = new Html();
       // $writer = new Xlsx($reader->loadFromString($content));
       // $writer->save(self::$homePage . $filename);

        if ($redirect) {
            $this->redirect("/xls/" . $filename);
        }
    }


    public function actionMail($id)
    {
        $model = $this->findModel($id);

        if (empty($model->artist->email)) {
            Yii::$app->session->setFlash('error', 'У артиста відстуній email');

            return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
        } else if ($model->getNotified()) {
            Yii::$app->session->setFlash('error', 'Цьому артисту вже відпавлено повідомлення');

            return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
        }
        // перевірка чи всі дані заповнені
        if($this->checkBeforeExport($model) !== true) {
            Yii::$app->session->setFlash('error', 'У артиста не заповнені всі дані');
            return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
        }

        $attach = [];

        $invoiceItemsIds = $this->getAllInvoiceItemsInProgressForArtist($model, InvoiceStatus::InProgress, 1);
        $name = Str::transliterate($model->artist->name) . "_" . implode('_', $invoiceItemsIds['invoice']);


        //$date = new \DateTime($model->invoice->date_pay);
        //$name = Str::transliterate($model->artist->name);

        //$actFileName = $date->format('Y_m_d') . "_{$name}_act_q{$model->invoice->quarter}_invoice_{$model->invoice->invoice_id}.pdf";
       /* $actFileName = $date->format('Y_m_d') . "_{$name}_act_q{$model->invoice->quarter}_invoice_{$model->invoice->invoice_id}.pdf";
        $act = self::$homePage . 'pdf/' . $actFileName;

         if (!file_exists($act)) {
             $this->actionPdfAct($id, false);
         }*/

        //$balanceFileName = $date->format('Y_m_d') . "_{$name}_balance_q{$model->invoice->quarter}_invoice_{$model->invoice->invoice_id}.pdf";
        $reportFileName = "report_{$name}_q{$model->invoice->quarter}_year_{$model->invoice->year}.xlsx";
        $excel =  self::$homePage .  'xls/' .$reportFileName;

        if (!file_exists($excel)) {
            $this->actionExportAct($id, false, $invoiceItemsIds);
        }

        $attach[] = [$excel, ['fileName' => $reportFileName]];

        $mail = new Mail([
            'from' => ['reports@blackbeatsmusic.com' => 'Black Beats Reports'],
            'to' => [$model->artist->email => $model->artist->name],
            'subject' => "Black Beats | Royalty Report Q{$model->invoice->quarter} {$model->invoice->year}",
            'bcc' => 'gmmkam123@gmail.com',
            'replyTo' => 'reports@blackbeatsmusic.com',
            'view' => [
                'html' => 'paymentNotification-html',
            ],
            'params' => [
                'InvoiceItems' => $model,
            ],
            'attach' => $attach,
        ]);

        if ($mail->send('Payment Notification', $model)) {
            foreach ($invoiceItemsIds['invoice'] as $invoice_id) {
                InvoiceLog::add($invoice_id, InvoiceLogType::EMAIL, $model->artist_id);
            }

            Yii::$app->session->setFlash('success', "Артисту {$model->artist->name} успішно відправлено звіт!");
        } else {
            Yii::$app->session->setFlash('error', "Артисту {$model->artist->name} не вдалось відправлено звіт! Зверніться до адміністратора.");
        }

        return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
    }

    public function actionApprove($id)
    {
        $model = $this->findModel($id);

        if ($model->getApproved()) {
            return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
        } /*else if (!$model->getNotified()) {
            Yii::$app->session->setFlash('error', 'Цьому артисту ще не відпавлено повідомлення');

            return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
        }*/

        if(InvoiceLog::add($model->invoice_id, InvoiceLogType::APPROVED, $model->artist_id)) {
            $tId = User::getTelegramId(14); // Тетяна бухгалтер

            if (!empty($tId)) {
                $message = "Підтверджено виплату артистом {$model->artist->name}.\nІнвойс {$model->invoice_id}";

                if (!empty($model->artist->iban)) {
                    $message .= "\nIBAN {$model->artist->iban}";
                }

                t::log($message, $tId);
            }
        }

        return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
    }

    public function actionPay($id)
    {
        $model = $this->findModel($id);

        if ($model->getPayed()) {
            return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
        } else if (!$model->getApproved()) {
            Yii::$app->session->setFlash('error', 'Цей артист не підтвердив виплату');

            return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
        }

        InvoiceLog::add($model->invoice_id, InvoiceLogType::PAYED, $model->artist_id);

        return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
    }

    /**
     * Finds the InvoiceItems model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return InvoiceItems the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel(int $id): InvoiceItems
    {
        if (($model = InvoiceItems::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
    private function getReportData(int $invoice_id, int $artist_id, bool $groupBy = false): \yii\db\DataReader|array
    {
        $query = "SELECT  a.name as artist_name,
                    t.name as track_name,
                    t2p.percentage,
                    t2p2.percentage as percentage_label,
                    o.name as prav1,
                    IFNULL(atu.name, a2ow.name) as prav2,
                    IFNULL(a_s.name, ari.platform) as platform,
                    ari.date_report,
                    ari.country,
                    c.currency_name
                    ";

        if ($groupBy) {
            $query .= ", sum(ari.count) as count,
               sum(IF(t2p.percentage != 100, t2p.percentage / 100 * ari.amount, ari.amount)) as amount";
        } else {
            $query .= ", ari.count,
             IF(t2p.percentage != 100, t2p.percentage / 100 * ari.amount, ari.amount) as amount";
        }

        $query .= "
        FROM `invoice_items` ii
                    LEFT JOIN artist a ON a.id = ii.artist_id
                    LEFT JOIN invoice_items ii2 ON ii2.payment_invoice_id = ii.invoice_id
                    INNER JOIN track t ON REPLACE(t.isrc, '-', '') = REPLACE(ii2.isrc, '-', '') and ii.artist_id = t.artist_id
                    INNER JOIN invoice i ON i.invoice_id = ii2.invoice_id
                     LEFT JOIN currency c ON c.currency_id= i.currency_id
                    INNER JOIN aggregator_report ar ON ar.id = i.aggregator_report_id and ar.report_status_id = 2
                    LEFT JOIN `aggregator_report_item` ari ON ari.report_id = ar.id and REPLACE(ii2.isrc, '-', '') = REPLACE(ari.isrc, '-', '')
                    LEFT JOIN aggregator agg ON agg.aggregator_id = ar.aggregator_id
                    LEFT JOIN aggregator_type_use atu ON atu.type_id = agg.type_use_id
                    LEFT JOIN aggregator_service a_s ON a_s.service_id = agg.service_id
                    LEFT JOIN (
                        SELECT aggregator_id, ownership_type_id , GROUP_CONCAT(ot_.name) as name
                        FROM aggregator_to_ownership_type 
                            LEFT JOIN ownership_type ot_ ON ot_.id = ownership_type_id 
                        GROUP BY aggregator_id
                    ) as a2ow ON a2ow.aggregator_id = agg.aggregator_id 
                    LEFT JOIN ownership o ON o.id = agg.ownership_type 
                    LEFT JOIN (
                        SELECT 100 / count(a2ot.id) * SUM(t2p.percentage) / 100 AS percentage, a2ot.aggregator_id, t2p.track_id, t2p.artist_id
                            FROM track_to_percentage t2p
                            LEFT JOIN aggregator_to_ownership_type a2ot ON a2ot.ownership_type_id = t2p.ownership_type
                        WHERE t2p.artist_id = :artist_id
                        GROUP BY t2p.artist_id, t2p.track_id, a2ot.aggregator_id
                    ) as t2p ON t2p.aggregator_id = agg.aggregator_id 
                        and t2p.artist_id = ii2.artist_id 
                        and t2p.track_id = t.id
                   # LEFT JOIN track_to_percentage t2p ON t2p.track_id = t.id and t2p.artist_id = a.id and t2p.ownership_type = a2ow.ownership_type_id 
                    LEFT JOIN track_to_percentage t2p2 ON t2p2.track_id = t.id and t2p2.artist_id = a.id and t2p2.ownership_type = 5 
                WHERE ii.artist_id = ii2.artist_id
                  #AND t2p.percentage > 0 
                  #AND t2p2.percentage > 0
                  AND ii.invoice_id =:invoice_id
                  AND t.artist_id =:artist_id";

        if ($groupBy) {
            $query .= "
             GROUP BY t.id
             ORDER BY t.id ASC
            ";
        } else {
            $query .= "
            #GROUP BY t.id, ari.platform, ari.country, ari.date_report
            ORDER BY t.id ASC, ari.`date_report` ASC
        ";
        }

        return Yii::$app->db->createCommand($query)
            ->bindValue(':invoice_id', $invoice_id)
            ->bindValue(':artist_id', $artist_id)
            ->queryAll();
    }

    private function getReportDataFeat(int $invoice_id, int $artist_id, bool $groupBy = false): \yii\db\DataReader|array
    {
        $query = "SELECT  a.name as artist_name,
                    a2.name as feat_name,
                    t.name as track_name,
                    t2p.percentage,
                    t2p2.percentage as percentage_label,
                    o.name as prav1,
                    IFNULL(atu.name, a2ow.name) as prav2,
                    IFNULL(a_s.name, ari.platform) as platform,
                    ari.date_report,
                    ari.country,
                    c.currency_name";

        if ($groupBy) {
            $query .=", sum(ari.count) as count,
             sum(IF(t2p.percentage != 100, t2p.percentage / 100 * ari.amount, ari.amount)) as amount";
        } else {
            $query .=", sum(ari.count) as count,
              sum(t2p.percentage / 100 * ari.amount) as amount";
        }

        $query .= "
        FROM `invoice_items` ii
                    LEFT JOIN artist a ON a.id = ii.artist_id
                    LEFT JOIN invoice_items ii2 ON ii2.payment_invoice_id = ii.invoice_id
                    INNER JOIN track t ON REPLACE(t.isrc, '-', '') = REPLACE(ii2.isrc, '-', '') 
                        and ii.artist_id != t.artist_id
                    LEFT JOIN artist a2 ON a2.id = t.artist_id
                    INNER JOIN invoice i ON i.invoice_id = ii2.invoice_id
                    LEFT JOIN currency c ON c.currency_id= i.currency_id
                    INNER JOIN aggregator_report ar ON ar.id = i.aggregator_report_id and ar.report_status_id = 2
                    LEFT JOIN `aggregator_report_item` ari ON ari.report_id = ar.id and REPLACE(ii2.isrc, '-', '') = REPLACE(ari.isrc, '-', '') and ari.amount > 0
                    LEFT JOIN aggregator agg ON agg.aggregator_id = ar.aggregator_id
                    LEFT JOIN aggregator_type_use atu ON atu.type_id = agg.type_use_id
                    LEFT JOIN aggregator_service a_s ON a_s.service_id = agg.service_id
                    LEFT JOIN (
                        SELECT aggregator_id, ownership_type_id , GROUP_CONCAT(ot_.name) as name
                        FROM aggregator_to_ownership_type 
                            LEFT JOIN ownership_type ot_ ON ot_.id = ownership_type_id 
                        GROUP BY aggregator_id
                    ) as a2ow ON a2ow.aggregator_id = agg.aggregator_id 
                    LEFT JOIN ownership o ON o.id = agg.ownership_type 
                    LEFT JOIN (
                        SELECT 100 / count(a2ot.id) * SUM(t2p.percentage) / 100 AS percentage, a2ot.aggregator_id, t2p.track_id, t2p.artist_id
                            FROM track_to_percentage t2p
                            LEFT JOIN aggregator_to_ownership_type a2ot ON a2ot.ownership_type_id = t2p.ownership_type
                        WHERE t2p.artist_id = :artist_id
                        GROUP BY t2p.artist_id, t2p.track_id, a2ot.aggregator_id
                    ) as t2p ON t2p.aggregator_id = agg.aggregator_id 
                        and t2p.artist_id = ii2.artist_id 
                        and t2p.track_id = t.id
                    LEFT JOIN track_to_percentage t2p2 ON t2p2.track_id = t.id and t2p2.artist_id = a.id and t2p2.ownership_type = 5 
                 WHERE  ii.artist_id = ii2.artist_id 
                     AND ar.report_status_id = 2
                    and ii.invoice_id =:invoice_id
                    and ii2.artist_id =:artist_id";

        if ($groupBy) {
            $query .= " GROUP BY t.id
             ORDER BY t.id ASC";
        } else {
            $query .= " GROUP BY t.id, ari.platform, ari.country, ari.date_report
             ORDER BY t.id ASC, ari.date_report ASC";
        }

        return Yii::$app->db->createCommand($query)
            ->bindValue(':invoice_id', $invoice_id)
            ->bindValue(':artist_id', $artist_id)
            ->queryAll();
    }

    private function getVutraty(Artist $artist, int $currency_id, ?Invoice $invoice = null): array
    {
        $lastPayInvoice = $artist->getLastPayInvoice($invoice->invoice_id, $currency_id);

        if (is_bool($lastPayInvoice)) {
            return Yii::$app->db->createCommand(
                "SELECT it.invoice_type_name,
                       ii.date_item,
                       a.name as a_name,
                       t.name as t_name,
                       ii.description,
                       ii.amount,
                       c.currency_name 
                    FROM `invoice_items` ii 
                        INNER JOIN invoice i ON i.invoice_id = ii.invoice_id 
                        INNER JOIN currency c ON c.currency_id = i.currency_id 
                        INNER join invoice_type it ON it.invoice_type_id = i.invoice_type
                        LEFT JOIN artist a ON a.id = ii.artist_id 
                        LEFT JOIN track t ON t.id = ii.track_id 
                        WHERE i.invoice_status_id in (2, 4) 
                          AND i.invoice_type IN (3, 4)
                          AND i.currency_id =:currency_id
                          AND ii.artist_id =:artist_id"
            )->bindValue(':artist_id', $artist->id)
            ->bindValue(':currency_id', $currency_id)
            ->queryAll();
        }

        return Yii::$app->db->createCommand(
            "SELECT it.invoice_type_name,
                       ii.date_item,
                       a.name as a_name,
                       t.name as t_name,
                       ii.description,
                       ii.amount,
                       c.currency_name 
                    FROM `invoice_items` ii 
                        INNER JOIN invoice i ON i.invoice_id = ii.invoice_id 
                        INNER JOIN currency c ON c.currency_id = i.currency_id 
                        INNER join invoice_type it ON it.invoice_type_id = i.invoice_type
                        LEFT JOIN artist a ON a.id = ii.artist_id 
                        LEFT JOIN track t ON t.id = ii.track_id 
                        WHERE i.invoice_status_id in (2, 4) 
                          AND i.invoice_type IN (3, 4)
                          AND i.currency_id =:currency_id
                          AND ii.artist_id =:artist_id
                          AND i.date_added <= :date_invoice
                          AND i.date_added > :date_last_pay"
        )->bindValue(':artist_id', $artist->id)
        ->bindValue(':currency_id', $currency_id)
        ->bindValue(':date_invoice', $invoice->date_pay)
        ->bindValue(':date_last_pay', $lastPayInvoice['date_pay'])
        ->queryAll();
    }

    private function checkBeforeExport(InvoiceItems $model)
    {
        $artist = $model->artist;

        switch ($artist->artist_type_id)
        {
            case '1': // ФІЗ

                if (empty($artist->contract) || empty($artist->full_name)) {
                    $error = "";

                    if (empty($artist->full_name)) {
                        $error = "В артиста {$artist->name} не вкзано ФІО";
                    } else if (empty($artist->contract)) {
                        $error = "В артиста {$artist->name} не вкзано № договору";
                    }

                    Yii::$app->session->setFlash('error', $error);

                    return false;
                }

                break;
            case '2': // ФОП
                if (empty($artist->tov_name) || empty($artist->full_name) || empty($artist->contract) || empty($artist->iban) ) {
                    $error = "";

                    if (empty($artist->full_name)) {
                        $error = "В артиста {$artist->name} не вкзано ФІО";
                    } else if (empty($artist->tov_name)) {
                        $error = "В артиста {$artist->name} не вкзано назву ТОВ";
                    } else if (empty($artist->contract)) {
                        $error = "В артиста {$artist->name} не вкзано № договору";
                    } else if (empty($artist->iban)) {
                        $error = "В артиста {$artist->name} не вкзано реквізити";
                    }

                    Yii::$app->session->setFlash('error', $error);

                    return false;
                }

                break;
        }

        return true;
    }

    private function getAllInvoiceItemsInProgressForArtist(InvoiceItems $invoiceItem, ?int $statusId = null, ?int $logTypeId = null): array
    {
        $result = [
            'invoice' => [$invoiceItem->invoice_id],
            'items' => [$invoiceItem->id]
        ];
        $q = " SELECT distinct ii.id, ii.invoice_id
            FROM invoice_items ii
                INNER JOIN `invoice` as i ON i.invoice_id = ii.invoice_id ";

        if (!empty($statusId)) {
            $q .= " AND i.invoice_status_id = {$statusId}
            ";
        }

        $q .= "
            AND i.invoice_type = 2 
            AND i.quarter = {$invoiceItem->invoice->quarter}
            AND i.`year` = {$invoiceItem->invoice->year}
            AND i.invoice_id != {$invoiceItem->invoice_id}
            ";

        if (!empty($logTypeId)) {
            $q .= " LEFT JOIN invoice_log il ON il.invoice_id = i.invoice_id 
                    and il.artist_id = ii.artist_id 
                    and il.log_type_id = {$logTypeId}
                    ";
        }
        $q .= " WHERE ii.artist_id = {$invoiceItem->artist_id}";

        if (!empty($logTypeId)) {
            $q .= " AND il.log_type_id is null";
        }

        $temp = Yii::$app->db->createCommand($q)
            ->queryAll();

        foreach ($temp as $item) {
            $result['invoice'][] = (int) $item['invoice_id'];
            $result['items'][] = (int) $item['id'];
        }

        return $result;
    }

}
