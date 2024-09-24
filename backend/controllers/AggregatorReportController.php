<?php

namespace backend\controllers;

use backend\models\AggregatorReportItem;
use backend\models\AggregatorReportItemSearch;
use backend\models\AggregatorReportStatus;
use backend\models\Artist;
use backend\models\Invoice;
use backend\models\InvoiceItems;
use backend\models\InvoiceItemsSearch;
use backend\models\InvoiceStatus;
use backend\models\Track;
use common\models\t;
use Yii;
use backend\models\AggregatorReport;
use backend\models\AggregatorReportSearch;
use yii\db\Exception;
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
        $loaded = InvoiceItems::find()
                ->select([InvoiceItems::tableName() . '.isrc'])
                ->from(InvoiceItems::tableName())
                ->InnerJoin(Invoice::tableName(), Invoice::tableName() . '.invoice_id = '. InvoiceItems::tableName() .' .invoice_id')
                ->where([
                    Invoice::tableName() . '.aggregator_report_id' => $id
                ])
                ->groupBy([InvoiceItems::tableName() . '.isrc'])
                ->asArray()
                ->all();

        $loaded = array_column($loaded, 'isrc');

        $loadedCount = count($loaded);

        $notLoaded = AggregatorReportItem::find()
            ->select([AggregatorReportItem::tableName() . '.isrc'])
            ->from(AggregatorReportItem::tableName())
            ->where([
                AggregatorReportItem::tableName() . '.report_id' => $id
            ])
            ->groupBy([AggregatorReportItem::tableName() . '.isrc'])
            ->asArray()
            ->all();

        $inReport = array_column($notLoaded, 'isrc');

        $searchModel = new AggregatorReportItemSearch();

        $query = Yii::$app->request->queryParams;
        $query['AggregatorReportItemSearch']['report_id'] = $id;
        $dataProvider = $searchModel->search($query);

        return $this->render('view', [
            'perc' => round(100 / count($inReport) * $loadedCount, 2),
            'model' => $model,
            'loaded' => $loaded,
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
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionGenerateInvoice(int $id)
    {
        $report = $this->findModel($id);

        if (!in_array($report->report_status_id, [AggregatorReportStatus::LOADED, AggregatorReportStatus::CONFLICT])) {
            throw new \RuntimeException('Не можна генерувати інвойс, для цього репорту');
        }

        $loaded = InvoiceItems::find()
            ->select([InvoiceItems::tableName() . '.isrc'])
            ->from(InvoiceItems::tableName())
            ->InnerJoin(Invoice::tableName(), Invoice::tableName() . '.invoice_id = '. InvoiceItems::tableName() .' .invoice_id')
            ->where([
                Invoice::tableName() . '.aggregator_report_id' => $id
            ])
            ->groupBy([InvoiceItems::tableName() . '.isrc'])
            ->asArray()
            ->all();

        $loaded = array_column($loaded, 'isrc');

        $is_have = (bool) count($loaded);

        $reportItems = (new \yii\db\Query())
            ->from(AggregatorReportItem::tableName())
            ->select('isrc, SUM(amount) as amount')
            ->where(['report_id' => $id])
            ->andFilterWhere(['not in', 'isrc', $loaded])
            ->groupBy(['isrc'])
            ->all();

        if (empty($reportItems)) {
            Yii::$app->session->setFlash('error', "Відсутні записи у звіті");

            return $this->redirect(['/aggregator-report/view', 'id' => $id]);
        }

        $tracks = [];
        $total = 0;

        $emptyTrack = [];

        foreach ($reportItems as $item) {
            $track = Track::getTrackByIsrc($item['isrc']);

            if (null === $track) {
                $emptyTrack[] = $item['isrc'];

                continue;
            }

            $tracks[$item['isrc']] = [
                'track' => $track,
                'amount' => $item['amount'],
            ];
        }

        if (empty($tracks)) {
            $message = 'Відсутні дані треків в системі.' . PHP_EOL;

            if (count($emptyTrack)) {
                $message .= ' Не знайдено ' . count($emptyTrack) . ' треків';
            }

            Yii::$app->session->setFlash('error', $message);

            return $this->redirect(['/aggregator-report/view', 'id' => $id]);
        }

        if ($is_have) {
            $invoice = Invoice::find()
                ->where(['aggregator_report_id' => $id])
                ->one();

            if (!$invoice instanceof Invoice || empty($invoice->invoice_id)) {
                Yii::$app->session->setFlash('error', "Інвойс інвалід");

                return $this->redirect(['/aggregator-report/view', 'id' => $id]);
            }
        } else {
            $invoice = new Invoice();
            $invoice->user_id = Yii::$app->user->getId();
            $invoice->invoice_type = 1;
            $invoice->invoice_status_id = InvoiceStatus::Generated;
            $invoice->aggregator_id = $report->aggregator_id;
            $invoice->aggregator_report_id = $report->id;
            $invoice->currency_id = $report->aggregator->currency_id;
            $invoice->total = $total;

            if (!$invoice->validate() || !$invoice->save()) {
                Yii::$app->session->setFlash('error', 'Помилка створення інвойсу для репорту: ' . $report->id);
                return $this->redirect(['/aggregator-report/view', 'id' => $id]);
            }
        }

        $total2 = 0;

        try {
            foreach ($tracks as $item) {
                $calculation = $item['track']->getCalculation($invoice->aggregator_id, $item['amount']);

                foreach ($calculation as $value) {
                    $invoiceItem = new InvoiceItems();
                    $invoiceItem->invoice_id = $invoice->invoice_id;
                    $invoiceItem->track_id = $item['track']->id;
                    $invoiceItem->isrc = $item['track']->isrc;
                    $invoiceItem->artist_id = $value['artist_id'];
                    $invoiceItem->amount = $value['amount'];

                    $total2 += $value['amount'];

                    if (!$invoiceItem->save()) {
                        if (!$is_have) {
                            InvoiceItems::deleteAll(['invoice_id' => $invoice->invoice_id]);
                            $invoice->delete();
                        }

                        $report->report_status_id = AggregatorReportStatus::CONFLICT; // Конфлікт
                        $report->save();

                        throw new \RuntimeException('Помилка збереження даних для треку: ' . $item['track']->id);
                    }
                }
            }

            if ($total2 == 0) {
                throw new \InvalidArgumentException('Нульовий тотал' . PHP_EOL . implode(',', array_keys($tracks)));
            }

            if (count($emptyTrack) > 0) {
                $report->report_status_id = AggregatorReportStatus::CONFLICT;
                $report->description = 'В системі не занйдено ' . count($emptyTrack) . ' трека';
            } else {
                $report->report_status_id = AggregatorReportStatus::GENERATED_INVOICE; // Згенерований інвойс
                $report->description = '';
            }
             $report->save();

            if ($invoice->invoice_status_id != InvoiceStatus::Calculated) {
                $invoice->invoice_status_id = InvoiceStatus::Calculated;
            }

            if ($is_have) {
                $invoice->total += $total2;
            } else {
                $invoice->total = $total2;
            }

            $invoice->save();
        } catch (\Throwable $e) {

            if (!$is_have) {
                InvoiceItems::deleteAll(['invoice_id' => $invoice->invoice_id]);
                $invoice->delete();
            }

           t::log($e->getMessage());

            Yii::$app->session->setFlash('error', 'Помилка генерації інвойсу: ' . $e->getMessage());

            return $this->redirect(['/aggregator-report/view', 'id' => $id]);
        }

        Artist::calculationDeposit();

        return $this->redirect(['/invoice/view', 'id' => $invoice->invoice_id]);
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
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        if ($model->report_status_id != 2) {
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
