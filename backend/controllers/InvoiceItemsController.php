<?php

namespace backend\controllers;

use backend\models\Artist;
use backend\models\Invoice;
use backend\models\Track;
use backend\widgets\Str;
use kartik\mpdf\Pdf;
use PhpOffice\PhpSpreadsheet\Reader\Html;
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

        $model->delete();

        $model->invoice->calculate();

        if ($model->invoice->invoice_type == 2 && $model->invoice->invoice_status_id == 2) {
            Artist::calculationDeposit($model->artist_id);
        }

        if (!empty($url)) {
            return $this->redirect($url);
        }

        return $this->redirect(['index']);
    }

    public function actionPdfBalance(int $id)
    {
        $model = $this->findModel($id);
        $d = date('Y_m_d');
        $name = Str::transliterate($model->artist->name);
        $filename = "/home/atpjwxlx/domains/blck.link/public_html/backend/web/pdf/balance_q3_{$d}_{$name}.pdf";

        if (file_exists($filename)) {
            // $this->redirect("/pdf/balance_q3_{$d}_{$name}.pdf");
        }

        $this->layout = 'pdf';

        $all = Yii::$app->db->createCommand(
                            "SELECT alt.name, `al`.`sum`, c.currency_name
                                    FROM `artist_log` `al` 
                                    INNER JOIN artist_log_type alt ON alt.log_type_id = `al`.`type_id` 
                                    INNER JOIN currency c ON c.currency_id = `al`.`currency_id`
                                 WHERE al.artist_id =:artist_id
                                    and al.quarter =:quarter
                                    and YEAR(`date_added`) =:year
                                    and `al`.currency_id =:currency_id")
            ->bindValue(':artist_id', $model->artist_id)
            ->bindValue(':quarter', 3)
            ->bindValue(':year', date('Y'))
            ->bindValue(':currency_id', $model->invoice->currency_id)
            ->queryAll();

        $costs = Yii::$app->db->createCommand(
            "SELECT it.invoice_type_name, ii.date_item, a.name as a_name, t.name as t_name, ii.description, ii.amount, c.currency_name 
FROM `invoice_items` ii 
    LEFT JOIN artist a ON a.id = ii.artist_id 
    LEFT JOIN track t ON t.id = ii.track_id 
    LEFT JOIN invoice i ON i.invoice_id = ii.invoice_id 
    LEFT JOIN currency c ON c.currency_id = i.currency_id 
    left join invoice_type it ON it.invoice_type_id = i.invoice_type 
    WHERE i.invoice_status_id = 2 
      and i.invoice_type in (3, 4) 
      and i.currency_id =:currency_id
    #and i.date_added > a.date_last_payment
      AND i.quarter =:quarter
      and ii.artist_id =:artist_id")
            ->bindValue(':artist_id', $model->artist_id)
            ->bindValue(':quarter', 3)
            ->bindValue(':currency_id', $model->invoice->currency_id)
            ->queryAll();

        $content = $this->render(
            'pdf/balance/view',
            [
                'model' => $model,
                'all' => $all,
                'costs' => $costs,
            ]
        );

        // return $content;

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
            'marginLeft' => 10,
            'marginTop' => 10,
            'marginRight' => 10,
            'marginHeader' => 5,
            // your html content input
            'content' => $content,
            // format content from your own css file if needed or use the
            // enhanced bootstrap css built by Krajee for mPDF formatting
            // 'cssFile' => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.css',
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
        $pdf->filename = $filename;
        // return the pdf output as per the destination setting
        $pdf->render();

        $this->redirect("/pdf/balance_q3_{$d}_{$name}.pdf");
    }

    public function actionPdfAct(int $id)
    {
        $model = $this->findModel($id);
        $d = date('Y_m_d');
        $name = Str::transliterate($model->artist->name);
        $filename = "/home/atpjwxlx/domains/blck.link/public_html/backend/web/pdf/act_q3_{$d}_{$name}.pdf";

        if (file_exists($filename)) {
            $this->redirect("/pdf/act_q3_{$d}_{$name}.pdf");
        }

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

                    return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
                }

                break;
            case '2': // ФОП
                if (empty($artist->tov_name) || empty($artist->full_name) || empty($artist->contract)) {
                    if (empty($artist->full_name)) {
                        $error = "В артиста {$artist->name} не вкзано ФІО";
                    } else if (empty($artist->tov_name)) {
                        $error = "В артиста {$artist->name} не вкзано назву ТОВ";
                    } else if (empty($artist->contract)) {
                        $error = "В артиста {$artist->name} не вкзано № договору";
                    }

                    return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
                }

                break;
        }

        $this->layout = 'pdf';

        $data = Yii::$app->db->createCommand(
            "SELECT  a.name as artist_name,
                    t.name as track_name,
                    ari.count, 
                    t2p.percentage,
                    ari.amount,
                    t2p2.percentage as pr2,
                    (t2p2.percentage / 100 * (t2p.percentage / 100 *ari.amount)) as am_2,
                    o.name as prav1,
                    ot.name as prav2,
                    ari.platform, ari.date_report 
                FROM `invoice_items` ii
                    LEFT JOIN artist a ON a.id = ii.artist_id
                    LEFT JOIN invoice_items ii2 ON ii2.payment_invoice_id = ii.invoice_id
                    INNER JOIN track t ON t.isrc = ii2.isrc and ii.artist_id = t.artist_id
                    INNER JOIN invoice i ON i.invoice_id = ii2.invoice_id
                    LEFT JOIN aggregator_report ar ON ar.id = i.aggregator_report_id
                    LEFT JOIN `aggregator_report_item` ari ON ari.report_id = ar.id and ii2.isrc = ari.isrc
                    LEFT JOIN aggregator agg ON agg.aggregator_id = ar.aggregator_id
                    LEFT JOIN aggregator_to_ownership_type a2ow ON a2ow.aggregator_id = agg.aggregator_id 
                    LEFT JOIN ownership o ON o.id = agg.ownership_type 
                    LEFT JOIN ownership_type ot ON ot.id = a2ow.ownership_type_id 
                    LEFT JOIN track_to_percentage t2p ON t2p.track_id = t.id and t2p.artist_id = a.id and t2p.ownership_type = a2ow.ownership_type_id 
                    LEFT JOIN track_to_percentage t2p2 ON t2p2.track_id = t.id and t2p2.artist_id = a.id and t2p2.ownership_type = 5 
                WHERE ii.artist_id = ii2.artist_id
                  AND t2p.percentage > 0 
                  AND t2p2.percentage > 0
                  AND ii.invoice_id =:invoice_id
                  AND t.artist_id =:artist_id")
            ->bindValue(':invoice_id', $model->invoice_id)
            ->bindValue(':artist_id', $model->artist_id)
            ->queryAll();

        $feats = Yii::$app->db->createCommand(
            "SELECT  a.name as artist_name,
                        a2.name as feat_name,
                    t.name as track_name,
                    ari.count, 
                    t2p.percentage,
                    ari.amount,
                    t2p2.percentage as pr2,
                    (t2p2.percentage / 100 * (t2p.percentage / 100 *ari.amount)) as am_2,
                    o.name as prav1,
                    ot.name as prav2,
                    ari.platform, ari.date_report 
                 FROM `invoice_items` ii
                    LEFT JOIN artist a ON a.id = ii.artist_id
                    LEFT JOIN invoice_items ii2 ON ii2.payment_invoice_id = ii.invoice_id
                    INNER JOIN track t ON t.isrc = ii2.isrc and ii.artist_id != t.artist_id
                    LEFT JOIN artist a2 ON a2.id = t.artist_id
                    INNER JOIN invoice i ON i.invoice_id = ii2.invoice_id
                    LEFT JOIN aggregator_report ar ON ar.id = i.aggregator_report_id
                    LEFT JOIN `aggregator_report_item` ari ON ari.report_id = ar.id and ii2.isrc = ari.isrc
                    LEFT JOIN aggregator agg ON agg.aggregator_id = ar.aggregator_id
                    LEFT JOIN aggregator_to_ownership_type a2ow ON a2ow.aggregator_id = agg.aggregator_id 
                    LEFT JOIN ownership o ON o.id = agg.ownership_type 
                    LEFT JOIN ownership_type ot ON ot.id = a2ow.ownership_type_id 
                    LEFT JOIN track_to_percentage t2p ON t2p.track_id = t.id and t2p.artist_id = a.id and t2p.ownership_type = a2ow.ownership_type_id 
                    LEFT JOIN track_to_percentage t2p2 ON t2p2.track_id = t.id and t2p2.artist_id = a.id and t2p2.ownership_type = 5 
                 WHERE  ii.artist_id = ii2.artist_id 
                    and ii.invoice_id =:invoice_id
                    and ii2.artist_id =:artist_id")
            ->bindValue(':invoice_id', $model->invoice_id)
            ->bindValue(':artist_id', $model->artist_id)
            ->queryAll();

        $content = $this->render(
            'pdf/' . $artist->artist_type_id . '/act',
            [
                'model' => $model,
                'items' => $data,
                'feats' => $feats,
            ]
        );

      // return $content;

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
            'marginLeft' => 10,
            'marginTop' => 10,
            'marginRight' => 10,
            'marginHeader' => 5,
            // your html content input
            'content' => $content,
            // format content from your own css file if needed or use the
            // enhanced bootstrap css built by Krajee for mPDF formatting
           // 'cssFile' => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.css',
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
        $pdf->filename = $filename;
        // return the pdf output as per the destination setting
        $pdf->render();

        $this->redirect("/pdf/act_q3_{$d}_{$name}.pdf");
    }

    public function actionExportAct(int $id)
    {
        $model = $this->findModel($id);

        $d = date('Y_m_d');
        $name = Str::transliterate($model->artist->name);
        $filename = "/home/atpjwxlx/domains/blck.link/public_html/backend/web/act_q3_{$d}_{$name}.xlsx";

        if (file_exists($filename)) {
            $this->redirect("/act_q3_{$d}_{$name}.xlsx");
        }

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

                    return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
                }

                break;
            case '2': // ФОП
                if (empty($artist->tov_name) || empty($artist->full_name) || empty($artist->contract)) {
                    if (empty($artist->full_name)) {
                        $error = "В артиста {$artist->name} не вкзано ФІО";
                    } else if (empty($artist->tov_name)) {
                        $error = "В артиста {$artist->name} не вкзано назву ТОВ";
                    } else if (empty($artist->contract)) {
                        $error = "В артиста {$artist->name} не вкзано № договору";
                    }

                    return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
                }

                break;
        }

        $this->layout = 'pdf';

        $data = Yii::$app->db->createCommand(
            "SELECT  a.name as artist_name,
                    t.name as track_name,
                    ari.count, 
                    t2p.percentage,
                    ari.amount,
                    t2p2.percentage as pr2,
                    (t2p2.percentage / 100 * (t2p.percentage / 100 *ari.amount)) as am_2,
                    o.name as prav1,
                    ot.name as prav2,
                    ari.platform, ari.date_report 
                FROM `invoice_items` ii
                    LEFT JOIN artist a ON a.id = ii.artist_id
                    LEFT JOIN invoice_items ii2 ON ii2.payment_invoice_id = ii.invoice_id
                    INNER JOIN track t ON t.isrc = ii2.isrc and ii.artist_id = t.artist_id
                    INNER JOIN invoice i ON i.invoice_id = ii2.invoice_id
                    LEFT JOIN aggregator_report ar ON ar.id = i.aggregator_report_id
                    LEFT JOIN `aggregator_report_item` ari ON ari.report_id = ar.id and ii2.isrc = ari.isrc
                    LEFT JOIN aggregator agg ON agg.aggregator_id = ar.aggregator_id
                    LEFT JOIN aggregator_to_ownership_type a2ow ON a2ow.aggregator_id = agg.aggregator_id 
                    LEFT JOIN ownership o ON o.id = agg.ownership_type 
                    LEFT JOIN ownership_type ot ON ot.id = a2ow.ownership_type_id 
                    LEFT JOIN track_to_percentage t2p ON t2p.track_id = t.id and t2p.artist_id = a.id and t2p.ownership_type = a2ow.ownership_type_id 
                    LEFT JOIN track_to_percentage t2p2 ON t2p2.track_id = t.id and t2p2.artist_id = a.id and t2p2.ownership_type = 5 
                WHERE ii.artist_id = ii2.artist_id
                  AND t2p.percentage > 0 
                  AND t2p2.percentage > 0
                  AND ii.invoice_id =:invoice_id
                  AND t.artist_id =:artist_id")
            ->bindValue(':invoice_id', $model->invoice_id)
            ->bindValue(':artist_id', $model->artist_id)
            ->queryAll();

        $feats = Yii::$app->db->createCommand(
            "SELECT  a.name as artist_name,
                        a2.name as feat_name,
                    t.name as track_name,
                    ari.count, 
                    t2p.percentage,
                    ari.amount,
                    t2p2.percentage as pr2,
                    (t2p2.percentage / 100 * (t2p.percentage / 100 *ari.amount)) as am_2,
                    o.name as prav1,
                    ot.name as prav2,
                    ari.platform, ari.date_report 
                 FROM `invoice_items` ii
                    LEFT JOIN artist a ON a.id = ii.artist_id
                    LEFT JOIN invoice_items ii2 ON ii2.payment_invoice_id = ii.invoice_id
                    INNER JOIN track t ON t.isrc = ii2.isrc and ii.artist_id != t.artist_id
                    LEFT JOIN artist a2 ON a2.id = t.artist_id
                    INNER JOIN invoice i ON i.invoice_id = ii2.invoice_id
                    LEFT JOIN aggregator_report ar ON ar.id = i.aggregator_report_id
                    LEFT JOIN `aggregator_report_item` ari ON ari.report_id = ar.id and ii2.isrc = ari.isrc
                    LEFT JOIN aggregator agg ON agg.aggregator_id = ar.aggregator_id
                    LEFT JOIN aggregator_to_ownership_type a2ow ON a2ow.aggregator_id = agg.aggregator_id 
                    LEFT JOIN ownership o ON o.id = agg.ownership_type 
                    LEFT JOIN ownership_type ot ON ot.id = a2ow.ownership_type_id 
                    LEFT JOIN track_to_percentage t2p ON t2p.track_id = t.id and t2p.artist_id = a.id and t2p.ownership_type = a2ow.ownership_type_id 
                    LEFT JOIN track_to_percentage t2p2 ON t2p2.track_id = t.id and t2p2.artist_id = a.id and t2p2.ownership_type = 5 
                 WHERE  ii.artist_id = ii2.artist_id 
                    and ii.invoice_id =:invoice_id
                    and ii2.artist_id =:artist_id")
            ->bindValue(':invoice_id', $model->invoice_id)
            ->bindValue(':artist_id', $model->artist_id)
            ->queryAll();

        $content = $this->render(
            'pdf/' . $artist->artist_type_id . '/act',
            [
                'model' => $model,
                'items' => $data,
                'feats' => $feats,
            ]
        );

        $reader = new Html();
        $writer = new Xlsx($reader->loadFromString($content));
        $writer->save($filename);

        $this->redirect("/act_q3_{$d}_{$name}.xlsx");
    }

    public function actionExportBalance(int $id)
    {
        $model = $this->findModel($id);
        $d = date('Y_m_d');
        $name = Str::transliterate($model->artist->name);
        $filename = "/home/atpjwxlx/domains/blck.link/public_html/backend/web/balance_q3_{$d}_{$name}.xlsx";

        if (file_exists($filename)) {
            $this->redirect("/balance_q3_{$d}_{$name}.xlsx");
        }

        $this->layout = 'pdf';

        $all = Yii::$app->db->createCommand(
            "SELECT alt.name, `al`.`sum`, c.currency_name
                                    FROM `artist_log` `al` 
                                    INNER JOIN artist_log_type alt ON alt.log_type_id = `al`.`type_id` 
                                    INNER JOIN currency c ON c.currency_id = `al`.`currency_id`
                                 WHERE al.artist_id =:artist_id
                                    and al.quarter =:quarter
                                    and YEAR(`date_added`) =:year")
            ->bindValue(':artist_id', $model->artist_id)
            ->bindValue(':quarter', 3)
            ->bindValue(':year', date('Y'))
            ->queryAll();

        $costs = Yii::$app->db->createCommand(
            "SELECT it.invoice_type_name, ii.date_item, a.name as a_name, t.name as t_name, ii.description, ii.amount, c.currency_name 
FROM `invoice_items` ii 
    LEFT JOIN artist a ON a.id = ii.artist_id 
    LEFT JOIN track t ON t.id = ii.track_id 
    LEFT JOIN invoice i ON i.invoice_id = ii.invoice_id 
    LEFT JOIN currency c ON c.currency_id = i.currency_id 
    left join invoice_type it ON it.invoice_type_id = i.invoice_type 
    WHERE i.invoice_status_id = 2 and i.invoice_type in (3, 4) and i.currency_id =:currency_id
    and i.date_added > a.date_last_payment and ii.artist_id =:artist_id")
            ->bindValue(':artist_id', $model->artist_id)
            ->bindValue(':currency_id', $model->invoice->currency_id)
            ->queryAll();

        $content = $this->render(
            'pdf/balance/view',
            [
                'model' => $model,
                'all' => $all,
                'costs' => $costs,
            ]
        );

        $reader = new Html();
        $writer = new Xlsx($reader->loadFromString($content));
        $writer->save($filename);

        $this->redirect("/balance_q3_{$d}_{$name}.xlsx");
    }

    /**
     * Finds the InvoiceItems model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return InvoiceItems the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = InvoiceItems::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
