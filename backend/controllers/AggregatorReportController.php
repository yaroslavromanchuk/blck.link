<?php

namespace backend\controllers;

use backend\models\AggregatorReportItem;
use backend\models\AggregatorReportItemSearch;
use backend\models\AggregatorReportStatus;
use backend\models\Artist;
use backend\models\Invoice;
use backend\models\InvoiceItems;
use backend\models\InvoiceStatus;
use backend\models\Track;
use backend\models\UserBalance;
use backend\models\UserToTrack;
use common\models\t;
use InvalidArgumentException;
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

        $loaded = InvoiceItems::find()
                ->select([InvoiceItems::tableName() . '.isrc'])
                ->from(InvoiceItems::tableName())
                ->InnerJoin(Invoice::tableName(), Invoice::tableName() . '.invoice_id = '. InvoiceItems::tableName() .' .invoice_id')
                ->where([Invoice::tableName() . '.aggregator_report_id' => $id])
                ->groupBy([InvoiceItems::tableName() . '.isrc'])
                ->asArray()
                ->all();

        $loaded = array_column($loaded, 'isrc');

        $loadedCount = count($loaded);

        $notLoaded = AggregatorReportItem::find()
            ->select([AggregatorReportItem::tableName() . '.isrc'])
            ->from(AggregatorReportItem::tableName())
            ->where([AggregatorReportItem::tableName() . '.report_id' => $id])
            //->andFilterWhere(['>', AggregatorReportItem::tableName() . '.amount', 0])
            ->groupBy([AggregatorReportItem::tableName() . '.isrc'])
            ->asArray()
            ->all();

        $inReport = array_column($notLoaded, 'isrc');

        $searchModel = new AggregatorReportItemSearch();

        $query = Yii::$app->request->queryParams;

        $query['AggregatorReportItemSearch']['report_id'] = $id;
        $dataProvider = $searchModel->search($query);

        if ($loadedCount) {
            $loaded = array_map(function($item) {
                return str_replace('-', '', $item);
            }, $loaded);

            $inReport2 = $inReport;

            $inReport = array_map(function($item) {
                return str_replace('-', '', $item);
            }, $inReport);

            $emptyIsrc = array_diff($inReport, $loaded);

            if (count($emptyIsrc) > 0) {
                Yii::$app->session->addFlash('error', 'В системі відсутні ISRC:');
                Yii::$app->session->addFlash('error', count($emptyIsrc) . ' треки');

                $inReport3 = array_flip($emptyIsrc);

                foreach ($emptyIsrc as $item) {
                    $item2 = $inReport2[$inReport3[$item]] ?? '';

                    if (!empty($item2) && $item2 != $item && $item == str_replace('-', '', $item2)) {
                        $item .= ' ('.$item2.')';
                    }
                    Yii::$app->session->addFlash('error', $item);
                }
            }
        }

        return $this->render('view', [
            'perc' => round(100 / count($inReport) * $loadedCount, 2),
            'model' => $model,
            'loaded' => $loaded,
           // 'notFound' => $notFound,
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

        if (!in_array($report->report_status_id, [AggregatorReportStatus::LOADED, AggregatorReportStatus::CONFLICT])) {
            throw new RuntimeException('Не можна генерувати інвойс, для цього репорту');
        }

        $loaded = InvoiceItems::find()
            ->select([InvoiceItems::tableName() . '.isrc'])
            ->from(InvoiceItems::tableName())
            ->InnerJoin(Invoice::tableName(), Invoice::tableName() . '.invoice_id = '. InvoiceItems::tableName() .' .invoice_id')
            ->where([Invoice::tableName() . '.aggregator_report_id' => $id])
            ->groupBy([InvoiceItems::tableName() . '.isrc'])
            ->asArray()
            ->all();

        $loaded = array_column($loaded, 'isrc');
        /*$loaded = array_map(function($item) {
            return str_replace('-', '', $item);
        }, $loaded);
*/
        $is_have = (bool) count($loaded);

        $reportItems = (new \yii\db\Query())
            ->from(AggregatorReportItem::tableName())
            ->select('isrc, SUM(amount) as amount, track_id')
            ->where(['report_id' => $id])
            ->andFilterWhere(['not in', 'isrc', $loaded])
            //->andFilterWhere(['>', AggregatorReportItem::tableName() . '.amount', 0])
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
            $isrc = trim($item['isrc']);
            $track = null;

            if (!empty($item['track_id'])) {
                $track = Track::findOne($item['track_id']);
            }

            if (is_null($track)) {
                $track = Track::getTrackByIsrc($isrc);
            }

            if (null === $track) {
                $emptyTrack[] = $isrc;

                continue;
            }

            if (isset($tracks[$track->isrc])) {
                $tracks[$track->isrc]['amount'] += round($item['amount'], 4);
            } else {
                $tracks[$track->isrc] = [
                    'track' => $track,
                    'amount' => round($item['amount'], 4),
                ];
            }
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
        }

        $total2 = 0;

        try {
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

                        if (!empty($value['from_artist_id'])) {
                            $invoiceItem->from_artist_id = $value['from_artist_id'];
                        }

                      /*  if ($value['artist_id'] == Artist::LABEL
                            && !empty($value['from_artist_id'])
                        ) {
                            // @var UserToTrack $user
                                foreach ($track->getUserToTracks() as $user) {
                                    $userBalance = new UserBalance();
                                    $userBalance->invoice_id = $invoice->invoice_id;
                                    $userBalance->currency_id = $invoice->currency_id;
                                    $userBalance->user_id = $user->user_id;
                                    $userBalance->track_id = $track->id;
                                    $userBalance->amount = round($value['amount'] * ($user->percentage / 100), 2);
                                    $userBalance->save();
                                }
                        }*/

                        $invoiceItem->amount = $value['amount'];

                        $total2 += $invoiceItem->amount;
                        $temp_amount += $invoiceItem->amount;

                        if (!$invoiceItem->save()) {
                            if (!$is_have) {
                                //InvoiceItems::deleteAll(['invoice_id' => $invoice->invoice_id]);
                                $invoice->delete();
                            }

                            $report->report_status_id = AggregatorReportStatus::CONFLICT; // Конфлікт
                            $report->save();

                            throw new RuntimeException('Помилка збереження даних для треку: ' . $item['track']->id . current($invoiceItem->getErrors()));
                        }
                    }

                    if (abs($temp_amount - $item['amount']) > 0.01) {
                        if (!$is_have) {
                            $invoice->delete();
                        }

                        $report->report_status_id = AggregatorReportStatus::LOADED; // Конфлікт
                        $report->save();

                        $m = 'Помилка розрахунку треку: ' . $track->isrc . '. Сума у звіті: ' . $item['amount'] . ', сума після розрахунку:' . $temp_amount;

                        t::log($m);

                        Yii::$app->session->setFlash('error', $m);

                        return $this->redirect(['/aggregator-report/view', 'id' => $id]);
                    }
                //}
            }

          //  if ($total2 == 0) {
             //   throw new InvalidArgumentException('Нульовий тотал' . PHP_EOL . implode(',', array_keys($tracks)));
          //  }

            if (count($emptyTrack) > 0) {
                $report->report_status_id = AggregatorReportStatus::CONFLICT;
                $report->description = 'В системі не занйдено ' . count($emptyTrack) . ' - ' . implode(',', $emptyTrack) . ' ISRC';
            } else {
                $report->report_status_id = AggregatorReportStatus::GENERATED_INVOICE; // Згенерований інвойс
                $report->description = '';
            }

             $report->save();

            if ($invoice->invoice_status_id != InvoiceStatus::Calculated
                && $report->report_status_id != AggregatorReportStatus::CONFLICT
            ) {
               // $invoice->invoice_status_id = InvoiceStatus::Calculated;
            }

            if ($is_have) {
                $invoice->total += $total2;
            } else {
                $invoice->total = $total2;
            }

            $invoice->save();
        } catch (Throwable $e) {

            if (!$is_have) {
                //InvoiceItems::deleteAll(['invoice_id' => $invoice->invoice_id]);
                $invoice->delete();
                UserBalance::deleteAll(['invoice_id' => $invoice->invoice_id]);
            }

           t::log($e->getMessage() . $e->getTraceAsString());

            Yii::$app->session->setFlash('error', 'Помилка генерації інвойсу: ' . $e->getMessage());

            return $this->redirect(['/aggregator-report/view', 'id' => $id]);
        }

        if ($invoice->invoice_status_id == InvoiceStatus::Calculated) {
            Artist::calculationDeposit();
        }
		
		Yii::$app->db->createCommand(
			"UPDATE aggregator_report_item a
					INNER JOIN track t ON t.isrc = a.isrc and t.isrc is not null
				SET a.`track_id` = t.id
				WHERE a.track_id is null and a.report_id = {$report->id}"
		)->execute();

        if (count($emptyTrack) > 0) {
            return $this->redirect(['/aggregator-report/view', 'id' => $id]);
        }

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
