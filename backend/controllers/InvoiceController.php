<?php

namespace backend\controllers;

use backend\models\Artist;
use backend\widgets\Str;
use kartik\mpdf\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yii;
use backend\models\Invoice;
use backend\models\InvoiceSearch;
use backend\models\InvoiceItems;
use backend\models\InvoiceItemsSearch;
use yii\base\InvalidConfigException;
use yii\bootstrap\ActiveForm;
use yii\data\SqlDataProvider;
use yii\db\Exception;
use yii\db\Query;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * InvoiceController implements the CRUD actions for Invoice model.
 */
class InvoiceController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
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
     * Lists all Invoice models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new InvoiceSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Invoice model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView(int $id)
    {
        $searchModel = new InvoiceItemsSearch();
        $query = Yii::$app->request->queryParams;
        $query['InvoiceItemsSearch']['invoice_id'] = $id;

        $dataProvider = $searchModel->search($query);

        $modelItems = new InvoiceItems();

        $query = new \yii\db\Query();
        $data = $query->from('invoice_items')
            ->select('SUM(IF(artist_id != 0, `amount`, 0)) AS `total_artist`, SUM(amount) AS `total`')
            ->where(['invoice_id' => $id])
            ->one();

        return $this->render('view', [
            'model' => $this->findModel($id),
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'modelItems' => $modelItems,
            'total' => $data
        ]);
    }

    /**
     * @param int $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionViewReport(int $id): string
    {
        return $this->render('view-report', [
            'model' => $this->findModel($id)
        ]);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function actionExport(int $id)
    {
        $sql = "SELECT #ss.artist_name,
                        #ss.track_name,
                        ss.summ,
                        ss.total 
                FROM (
                SELECT it.`artist_id`, a.name as artist_name, t.name as track_name, sum(it.amount) as summ, sum(DISTINCT(tt.am)) as total
                    FROM `invoice_items` it 
                    LEFT JOIN (
                     SELECT track_id, artist_id, invoice_id, sum(amount) as am 
                        FROM `invoice_items` 
                        WHERE invoice_id= {$id}
                        GROUP BY track_id
                    ) as tt ON tt.track_id = it.track_id
                    LEFT JOIN artist as a ON a.id = it.artist_id 
                    LEFT JOIN track as t ON t.id = it.track_id 
                        WHERE it.invoice_id = {$id}
                        GROUP BY it.artist_id, it.track_id
                ) as ss
                WHERE ss.artist_id != 0 ORDER BY `ss`.`track_name` ASC";



        $data = Yii::$app->db->createCommand($sql)->queryAll();

        $file = \Yii::createObject([
            'class' => 'codemix\excelexport\ExcelFile',
            'sheets' => [
                'Users' => [
                    'data' => $data,
                    'titles' => ['Artist', 'Track', 'Summ', 'Total'],
                ],
            ],
            //'sheets' => [

             //   'Users' => [
              //      'class' => 'codemix\excelexport\ActiveExcelSheet',
               ///     'query' => $data,
              //  ]
           // ]
        ]);

        $file->send($id. '_invoice_report.xlsx');
    }

    public function actionViewModal(int $id, int $artistId)
    {
        $searchModel = new InvoiceItemsSearch();
        $dataProvider = $searchModel->search(['InvoiceItemsSearch' => ['invoice_id' => $id, 'artist_id' => $artistId]]);

        return $this->renderAjax('view-modal', [
            'invoiceId' => $id,
            'items' => [
                'dataProvider' => $dataProvider,
            ]
        ]);
    }


    /**
     * Перерахунок суми інвойсу
     *
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionReCalculate(int $id)
    {
        $model = $this->findModel($id);

        if (!$model->getInvoiceItems()->count()) {

            Yii::$app->session->setFlash('error', 'В інвойсі відсутні записи для розрахунку');

            return $this->redirect(['view', 'id' => $id]);
        }

        $total_temp = $model->total;

        $model->calculate();

        if ($total_temp != $model->total) {
            Yii::$app->session->setFlash('success', 'Інвойс перераховано: ' . $total_temp . ' => ' . $model->total);
        } else {
            Yii::$app->session->setFlash('success', 'Інвойс корректний');
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Розрахунок інвойсу і оновлення депозівтів
     *
     * @param int $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionCalculate(int $id)
    {
        $model = $this->findModel($id);

        if (!$model->getInvoiceItems()->count()) {

            Yii::$app->session->setFlash('error', 'В інвойсі відсутні записи для розрахунку');

            return $this->redirect(['view', 'id' => $id]);
        }

        if ($model->invoice_type == 2) { // Виплата
            $db = Yii::$app->db;

            // записати останній інвойс і дату випалит в память акртисту
            foreach ($model->getInvoiceItems()->all() as $item) {

                $item->artist->saveBalance(3, $model->currency_id);

                $db->createCommand()
                    ->update('artist',
                        [
                            'date_last_payment' => date('Y-m-d', strtotime('2024-09-30')),
                            'last_payment_invoice' => $model->invoice_id
                        ],
                        'id = :ID',
                        [':ID' => $item->artist_id]
                    )->execute();

                $db->createCommand(
                    "UPDATE `invoice_items` ii 
                            INNER JOIN invoice i ON i.invoice_id = ii.invoice_id and i.invoice_type = 1 
                         SET ii.`payment_invoice_id`= {$model->invoice_id} 
                         WHERE ii.artist_id = {$item->artist_id} 
                         AND ii.payment_invoice_id is null 
                         AND i.currency_id = " .$model->currency_id
                )->execute();
            }

            $db->createCommand(
                "UPDATE aggregator_report_item ari 
                            INNER JOIN aggregator_report ar ON ar.id = ari.report_id 
                            INNER JOIN invoice i ON i.aggregator_report_id = ar.id and ar.report_status_id = 2 
                            INNER JOIN invoice_items ii ON ii.invoice_id = i.invoice_id and i.invoice_type = 1 and ii.payment_invoice_id = {$model->invoice_id}
                        SET ari.payment_invoice_id = {$model->invoice_id} 
                        WHERE ari.payment_invoice_id is null 
                            AND ari.isrc = ii.isrc"
            )->execute();
        }

        $model->calculate();
        $model->invoice_status_id = 2; // Розрахований
        $model->save();

        Artist::calculationDeposit();

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Creates a new Invoice model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Invoice();

        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            return ActiveForm::validate($model);
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->invoice_id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Invoice model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->invoice_id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Invoice model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

            if ($model->invoice_status_id != 2) {
                $model->delete();
           } else {
              Yii::$app->session->setFlash('error', "Неможа видалити розрахований інвойст");
            }

        return $this->redirect(['index']);
    }

    public function actionExportToExcel($id): void
    {
        $model = $this->findModel($id);

        $filename = "/home/atpjwxlx/domains/blck.link/public_html/backend/web/report_artist_income_q{$model->quarter}_{$model->invoice_id}.xlsx";

        if (file_exists($filename)) {
            $this->redirect("/report_artist_income_q{$model->quarter}_{$model->invoice_id}.xlsx");
        }

        $spreadSheet = new Spreadsheet();
        $workSheet = $spreadSheet->getActiveSheet();
        $workSheet->setTitle('Дохід артистів по звіту');

        $tempData = [];
        $tempData[] = [
            'Артист',
            'Сума',
            'Валюта'
        ];

        $tempData =array_merge($tempData,$model->getInvoiceReportDataGroupArtist());

        $workSheet->fromArray($tempData, null, 'A1');
        $writer = new Xlsx($spreadSheet);
        $writer->save($filename);

        $this->redirect("/report_artist_income_q{$model->quarter}_{$model->invoice_id}.xlsx");
    }

    /**
     * Finds the Invoice model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Invoice the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Invoice::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
