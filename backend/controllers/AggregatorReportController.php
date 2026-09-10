<?php

namespace backend\controllers;

use backend\helpers\Isrc;
use backend\models\AggregatorReportItem;
use backend\models\AggregatorReportItemSearch;
use backend\models\AggregatorReportStatus;
use backend\models\Artist;
use backend\models\Invoice;
use backend\models\InvoiceItems;
use backend\models\InvoiceStatus;
use backend\models\Track;
use common\models\t;
use RuntimeException;
use Throwable;
use Yii;
use backend\models\AggregatorReport;
use backend\models\AggregatorReportSearch;
use yii\db\StaleObjectException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * AggregatorReportController implements the CRUD actions for AggregatorReport model.
 */
class AggregatorReportController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all AggregatorReport models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new AggregatorReportSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single AggregatorReport model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        if ($model->report_status_id == AggregatorReportStatus::LOADED) {
            Yii::$app->db->createCommand(
                "UPDATE aggregator_report_item ari
                JOIN track t ON t.isrc = ari.isrc
			SET ari.`track_id` = t.id
			WHERE ari.track_id is null
			    and t.isrc is not null
			    and t.isrc != ''
			    and ari.report_id = {$id}"
            )->execute();
        }
        
        $searchModel = new AggregatorReportItemSearch();
        $query = Yii::$app->request->queryParams;
        $query['AggregatorReportItemSearch']['report_id'] = $id;
        $dataProvider = $searchModel->search($query);
        
        $request = AggregatorReportItem::find()
            ->where(['report_id' => $id])
            ->groupBy('isrc')
            ->andWhere(['track_id' => null]);
        
        $notLoaded = $request->count();
        
        if ($notLoaded) {
            $emptyIsrc = $request->select('isrc')->asArray()->all();
           // print_r($emptyIsrc);
            
            Yii::$app->session->addFlash('error', 'В системі відсутні ISRC:');
            Yii::$app->session->addFlash('error', $notLoaded . ' треки');
            
            foreach ($emptyIsrc as $item) {
                Yii::$app->session->addFlash('error', (new \backend\helpers\Isrc($item['isrc']))->getIsrc(true, true) . ' (' .$item['isrc']. ')' );
            }
        }
        
        $loaded = AggregatorReportItem::find()
            ->where(['report_id' => $id])
            ->groupBy('isrc')
            ->count();

        return $this->render('view', [
            'perc' => round(100 / $loaded * ($loaded-$notLoaded), 2),
            'model' => $model,
            'searchModel' => $searchModel,
            'items' => [
                'dataProvider' => $dataProvider,
            ]
        ]);
    }

    /**
     * @param int $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     * @throws Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionGenerateInvoice(int $id)
    {
        $report = $this->findModel($id);

        if(!$this->validateReportReady($report)) {
            return $this->redirect(['/aggregator-report/view', 'id' => $id]);
        }
        
        $reportItems = AggregatorReportItem::find()
            ->select('track_id, SUM(amount) as amount, isrc')
            ->where(['report_id' => $id])
            ->groupBy(['track_id'])
            ->all();

        if (empty($reportItems)) {
            Yii::$app->session->setFlash('error', "Відсутні записи у звіті");

            return $this->redirect(['/aggregator-report/view', 'id' => $id]);
        }

        $tracks = [];
        $total = 0;

        foreach ($reportItems as $item) {
            $track = Track::findOne($item->track_id);

            if (null === $track) {
                Yii::$app->session->setFlash('error', "Відсутній трек з ID: {$item->track_id} для ISRC: {$item->isrc} в системі.");
                return $this->redirect(['/aggregator-report/view', 'id' => $id]);
            }

            $tracks[$track->id] = [
                'track' => $track,
                'amount' => round($item->amount, 9),
            ];
        }

        if (empty($tracks)) {
            $message = 'Відсутні дані треків в системі.' . PHP_EOL;

            Yii::$app->session->setFlash('error', $message);

            return $this->redirect(['/aggregator-report/view', 'id' => $id]);
        }
        
        $tx = Yii::$app->db->beginTransaction();
        try {
            $invoice = new Invoice();
            $invoice->user_id = Yii::$app->user->getId();
            $invoice->invoice_type = 1;
            $invoice->invoice_status_id = InvoiceStatus::Generated;
            $invoice->aggregator_id = $report->aggregator_id;
            $invoice->aggregator_report_id = $report->id;
            $invoice->currency_id = $report->aggregator->currency_id;
            $invoice->exchange = 1;
            $invoice->quarter = $report->quarter;
            $invoice->year = $report->year;
            $invoice->total = $total;
            $invoice->description = 'Звіт ' . $report->aggregator->name . ' за ' . $report->quarter . 'кв.' . $report->year;
            
            if (!$invoice->validate()) {
                //print_r($invoice->getErrors());
                $er = current($invoice->getErrors());
                
                Yii::$app->session->setFlash(
                    'error',
                    current($er)
                );
                
                return $this->redirect(['/aggregator-report/view', 'id' => $id]);
            }
            
            if (!$invoice->save()) {
                Yii::$app->session->setFlash('error', 'Помилка створення інвойсу для репорту: ' . $report->id);
                return $this->redirect(['/aggregator-report/view', 'id' => $id]);
            }
            
            $total2 = 0;
            foreach ($tracks as $item) {
                //if ($item['amount'] > 0) {
                /** @var Track $track */
                $track = $item['track'];
                $calculation = $track->getCalculation($invoice->aggregator_id, $item['amount']);
                
                $temp_amount = 0.0;
                foreach ($calculation as $value) {
                    $invoiceItem = new InvoiceItems();
                    $invoiceItem->invoice_id = $invoice->invoice_id;
                    $invoiceItem->track_id = $track->id;
                    $invoiceItem->isrc = $track->isrc;
                    $invoiceItem->artist_id = $value['artist_id'];
                    $invoiceItem->date_item = date('Y-m-d');
                    $invoiceItem->percentage = $value['percentage'];
                    
                    if (isset($value['artist_percentage'])) {
                        $invoiceItem->artist_percentage = $value['artist_percentage'];
                    }
                    
                    if (!empty($value['from_artist_id'])) {
                        $invoiceItem->from_artist_id = $value['from_artist_id'];
                    }
                    
                    $invoiceItem->amount = $value['amount'];
                    
                    $total2 += $invoiceItem->amount;
                    $temp_amount += $invoiceItem->amount;
                    
                    if (!$invoiceItem->save()) {
                        throw new RuntimeException('Помилка збереження даних для треку: ' . $item['track']->id . current($invoiceItem->getErrors()));
                    }
                }
                
                if (abs($temp_amount - $item['amount']) > 0.01) {
                    $m = 'Помилка розрахунку треку: ' . $track->isrc . '. Сума у звіті: ' . $item['amount'] . ', сума після розрахунку:' . $temp_amount;
                    throw new RuntimeException('Помилка генерації інвойсу:' . $m);
                }
            }
            
            $report->report_status_id = AggregatorReportStatus::GENERATED_INVOICE; // Згенерований інвойс
            $report->description = '';
            
            if(!$report->save()) {
                $er = current($report->getErrors());
                throw new RuntimeException('Помилка генерації інвойсу:' . current($er));
            }
            
            $invoice->total = $total2;
            
            if(!$invoice->save(false)) {
                $er = current($invoice->getErrors());
                throw new RuntimeException('Помилка генерації інвойсу:' . current($er));
            }
            
            $tx->commit();
        } catch (Throwable $e) {
            $tx->rollBack();
            $report->report_status_id = AggregatorReportStatus::CONFLICT; // Конфлікт
            $report->save();
            
            Yii::$app->session->setFlash('error', 'Помилка генерації інвойсу: ' . $e->getMessage());
            return $this->redirect(['/aggregator-report/view', 'id' => $id]);
        }

        if ($invoice->invoice_status_id == InvoiceStatus::Calculated) {
            Artist::calculationDeposit();
        }

        return $this->redirect(['/invoice/view', 'id' => $invoice->invoice_id]);
    }
    
    private function validateReportReady(AggregatorReport $report): bool
    {
        if (!in_array($report->report_status_id, [
            AggregatorReportStatus::LOADED,
            AggregatorReportStatus::CONFLICT
        ])) {
            Yii::$app->session->setFlash('warning', "Не можна генерувати інвойс, для цього репорту");
            return false;
        }
        
        Yii::$app->db->createCommand(
            "UPDATE aggregator_report_item ari
                JOIN track t ON t.isrc = ari.isrc
			SET ari.`track_id` = t.id
			WHERE ari.track_id is null
			    and t.isrc is not null
			    and t.isrc != ''
			    and ari.report_id = {$report->id}"
        )->execute();
        
        $missing = AggregatorReportItem::find()
            ->where(['report_id' => $report->id])
            ->andWhere(['track_id' => null]);
        
        if ($missing->count() > 0) {
            //$report->report_status_id = AggregatorReportStatus::CONFLICT;
            $report->description = "Відсутні треки для ISRC";
            $report->save(false);
            
            Yii::$app->session->setFlash('warning', 'В системі відсутні треки для ISRC:');
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Creates a new AggregatorReport model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new AggregatorReport();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing AggregatorReport model.
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
     * Deletes an existing AggregatorReport model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     * @throws Throwable
     * @throws StaleObjectException
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        if (in_array($model->report_status_id, [1, 3]) && is_null(Invoice::findOne(['aggregator_report_id' => $model->id, 'invoice_type' => 1]))) {
            $model->delete();
        } else {
            Yii::$app->session->setFlash('error', "Неможа видалити репорт для якого згенеровано інвойст");
        }

        return $this->redirect(['index']);
    }

    /**
     * Finds the AggregatorReport model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return AggregatorReport the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = AggregatorReport::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
