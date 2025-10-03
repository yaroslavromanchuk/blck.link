<?php

namespace backend\controllers;

use backend\models\AggregatorReport;
use backend\models\AggregatorReportItem;
use backend\models\AggregatorReportStatus;
use backend\models\Artist;
use backend\models\InvoiceReport;
use backend\models\InvoiceStatus;
use backend\models\InvoiceType;
use backend\models\PayInvoiceReport;
use backend\models\Track;
use backend\models\UserBalance;
use backend\widgets\DateFormat;
use backend\widgets\Str;
use kartik\mpdf\Pdf;
use PhpOffice\PhpSpreadsheet\Reader\Html;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
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
    private static string $homePage = '/home/atpjwxlx/domains/blck.link/public_html/backend/web/';
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
        $queryParams = Yii::$app->request->queryParams;

        if (empty($queryParams['InvoiceSearch']['label_id'])) {
            $queryParams['InvoiceSearch']['label_id'] = 0;
        }

        $dataProvider = $searchModel->search($queryParams);

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
        $queryParams = Yii::$app->request->queryParams;
        $queryParams['InvoiceItemsSearch']['invoice_id'] = $id;

        $dataProvider = $searchModel->search($queryParams);

        $modelItems = new InvoiceItems();

        $query = new \yii\db\Query();
        $query->from('invoice_items')
            ->select('SUM(IF(artist_id != 0, `amount`, 0)) AS `total_artist`, SUM(amount) AS `total`')
            ->where(['invoice_id' => $id]);
        if (isset($queryParams['InvoiceItemsSearch']['artist_id']) && $queryParams['InvoiceItemsSearch']['artist_id'] >= 0) {
            $query->andWhere(['artist_id' => $queryParams['InvoiceItemsSearch']['artist_id']]);
        }

        if (isset($queryParams['InvoiceItemsSearch']['track_id']) && $queryParams['InvoiceItemsSearch']['track_id'] >= 0) {
            $query->andWhere(['track_id' => $queryParams['InvoiceItemsSearch']['track_id']]);
        }

        $data = $query->one();

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
     * @param int $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionFixTotal(int $id)
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
     * Перерахунок відсотків і суми інвойсу
     *
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionReCalculate(int $id)
    {
        $model = $this->findModel($id);

        $total_temp = $model->total;

        $reportItems = (new \yii\db\Query())
            ->from(AggregatorReportItem::tableName())
            ->select('track_id, SUM(amount) as amount')
            ->where(['report_id' => $model->aggregator_report_id])
            ->groupBy(['track_id'])
            ->all();

        if ($reportItems) {
            InvoiceItems::deleteAll(['invoice_id' => $model->invoice_id]);

            foreach ($reportItems as $item) {
                $track = Track::findOne($item['track_id']);
                $calculation = $track->getCalculation($model->aggregator_id, $item['amount']);

                    foreach ($calculation as $value) {
                        $invoiceItem = new InvoiceItems();
                        $invoiceItem->invoice_id = $model->invoice_id;
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
                        if(!$invoiceItem->save()) {
                            Yii::$app->session->setFlash('error', 'Помилка при збереженні інвойсу: ' . current($invoiceItem->getErrors()));
                            $model->invoice_status_id = InvoiceStatus::Error;

                            return $this->redirect(['view', 'id' => $id]);
                        }
                    }
                }
        }

        $model->calculate();

        Artist::calculationDeposit();

        if ($total_temp != $model->total) {
            Yii::$app->session->setFlash('success', 'Загальна сума інфойсу змінена: ' . $total_temp . ' => ' . $model->total);
        } else {
            Yii::$app->session->setFlash('success', 'Інвойс перераховано');
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

        if ($model->invoice_type == InvoiceType::$credit
            && $model->invoice_status_id == InvoiceStatus::Generated
        ) { // Виплата
			
			$artist = [];
			//foreach ($model->getInvoiceItems()->all() as $item) {
				//$artist[] = $item->artist_id;
				Yii::$app->db->createCommand(
					"UPDATE `invoice_items` ii
                            INNER JOIN invoice i ON i.invoice_id = ii.invoice_id
								and i.invoice_type in (1, 3, 4, 5)
								and i.invoice_status_id = 2
                         SET ii.`payment_invoice_id`= {$model->invoice_id}
                         WHERE ii.artist_id in (SELECT distinct(artist_id) FROM `invoice_items` ii WHERE ii.invoice_id = {$model->invoice_id})
                            AND ii.payment_invoice_id is null
                            AND i.currency_id = {$model->currency_id}"
				)->execute();
		//	}
			
			/*if (count($artist) > 0) {
				foreach (array_chunk($artist, 10) as $chunk) {
					$ids = implode(',', $chunk);
				}
				
			}*/
               /*  Yii::$app->db->createCommand()
                    ->update('artist',
                        [
                            'date_last_payment' => date('Y-m-d'),
                            'last_payment_invoice' => $model->invoice_id
                        ],
                        'id = :ID',
                        [':ID' => $item->artist_id]
                    )->execute();
                */


            Yii::$app->db->createCommand(
                "UPDATE aggregator_report_item ari 
                            INNER JOIN aggregator_report ar ON ar.id = ari.report_id 
                            INNER JOIN invoice i ON i.aggregator_report_id = ar.id
								and ar.report_status_id = 2
								and i.invoice_type = 1
								and i.invoice_status_id = 2
                            INNER JOIN invoice_items ii ON ii.invoice_id = i.invoice_id
                            	and ii.payment_invoice_id = {$model->invoice_id}
                        SET ari.payment_invoice_id = {$model->invoice_id} 
                        WHERE ari.payment_invoice_id is null 
                            AND ari.track_id = ii.track_id
                   ")->execute();
        }

        $model->calculate();

        if ($model->invoice_type == InvoiceType::$debit
        && abs($model->total - $model->aggregatorReport->total) > 1
        ) {
            Yii::$app->session->setFlash('error', 'Сума інвойсу не сходиться із сумою звіту агрегатора. Перевірте правильність заповнення інвойсу.');

            return $this->redirect(['view', 'id' => $id]);
        }

        if ($model->invoice_type == InvoiceType::$credit
			&& $model->invoice_status_id == InvoiceStatus::Generated
		) { // Виплата
            $model->invoice_status_id = InvoiceStatus::InProgress;
        } else {
            $model->invoice_status_id = InvoiceStatus::Calculated; // Розрахований
        }

        $model->save();

        if(in_array($model->invoice_status_id, [InvoiceStatus::InProgress, InvoiceStatus::Calculated])) {
            Artist::calculationDeposit();
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionAnalise(int $id)
    {
        $model = $this->findModel($id);

        if ($model->invoice_type != InvoiceType::$debit) {
            Yii::$app->session->setFlash('error', 'Можна аналізувати інвойси Надходження');

            return $this->redirect(['view', 'id' => $id]);
        }

        if ($model->invoice_status_id != InvoiceStatus::Generated) {
            Yii::$app->session->setFlash('error', 'Можна аналізувати інвойси лише в статусі Новий');

            return $this->redirect(['view', 'id' => $id]);
        }

        if (!$model->getInvoiceItems()->count()) {
            Yii::$app->session->setFlash('error', 'В інвойсі відсутні записи для розрахунку');

            return $this->redirect(['view', 'id' => $id]);
        }

        Yii::$app->session->setFlash('error', 'Аналіз інвойсу не реалізовано. Зверніться до адміністратора.');


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

        if ($model->load(Yii::$app->request->post())) {
            if (empty($model->aggregator_id)) {
                switch ($model->currency_id) {
                    case 1: $model->aggregator_id = 10; // EURO
                    break;
                    case 2: $model->aggregator_id = 11; // UAH
                        break;
                    case 3: $model->aggregator_id = 13; // USD
                        break;
                    default: $model->aggregator_id = 10; // EURO
                }
            }

            if ($model->save()) {
                return $this->redirect(['view', 'id' => $model->invoice_id]);
            }
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

            if (!in_array($model->invoice_status_id, [InvoiceStatus::InProgress, InvoiceStatus::Calculated])
                || Yii::$app->user->id == 1
            ) {
                if ($model->delete() !== false) {
                    if (!empty($model->aggregator_report_id)) {
                        $aggregatorReport = AggregatorReport::findOne($model->aggregator_report_id);
                        if (!is_null($aggregatorReport)) {
                            $aggregatorReport->report_status_id = 1;
                            $aggregatorReport->save();
                        }
                    }

                    $db = Yii::$app->db;
                    $db->createCommand(
                        "UPDATE `invoice_items` ii 
                        SET ii.`payment_invoice_id`= null
                         WHERE ii.`payment_invoice_id`= {$id}"
                    )->execute();

                    $db->createCommand(
                        "UPDATE aggregator_report_item ari 
                        SET ari.payment_invoice_id = null
                        WHERE ari.payment_invoice_id = {$id}"
                    )->execute();

                    if (in_array($model->invoice_type, [InvoiceType::$credit, InvoiceType::$debit])
						&& in_array($model->invoice_status_id, [InvoiceStatus::InProgress, InvoiceStatus::Calculated])
					) {
                        Artist::calculationDeposit();
                    }

                    if ($model->invoice_type == InvoiceType::$debit) {
                        UserBalance::deleteAll(['invoice_id' => $model->invoice_id]);
                    }
                }
           } else {
              Yii::$app->session->setFlash('error', "Неможа видалити розрахований інвойст");
            }

        return $this->redirect(['index']);
    }

    /**
     * Звіт по надходженням по кожному агрегатору
     * [артист, загальний дохід,частка артиста, частка лейбу]
     * @param $id
     * @return void
     * @throws NotFoundHttpException
     */
    public function actionExportToExcel($id): void
    {
        $model = $this->findModel($id);

        $filename = "report_aggregator_{$model->aggregator_id}_q{$model->quarter}_{$model->invoice_id}.xlsx";

        if (file_exists(self::$homePage . 'xls/' .$filename)) {
            $this->redirect("/xls/".$filename);
        }

        $spreadSheet = new Spreadsheet();
        $workSheet = $spreadSheet->getActiveSheet();
        $workSheet->setTitle('Дохід артистів по звіту');
        $workSheet->getStyle('A1:F1')->getAlignment()
            ->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workSheet->getColumnDimension('A')->setWidth(17);
        $workSheet->getColumnDimension('B')->setWidth(17);
        $workSheet->getColumnDimension('C')->setWidth(13);
        $workSheet->getColumnDimension('D')->setWidth(15);
        $workSheet->getColumnDimension('E')->setWidth(13);
        $workSheet->getColumnDimension('F')->setWidth(10);
        $workSheet->getStyle('A1:F1')->getFont()->setBold(true);

        $header = [
            [
                'Лейбл',
                'Артист',
                'Сума доходу',
                'Частка акртиста',
                'Частка лейбу',
                'Валюта'
            ]
        ];

        $data = $model->getInvoiceReportDataGroupArtist();

        $workSheet->fromArray(array_merge($header, $data), null, 'A1');

        if (count($data)) {
            $i = (count($data) +2);
            $workSheet->setCellValue('C' . $i, round(array_sum(array_column($data, 'all_sum')), 4));
            $workSheet->setCellValue('D' . $i, round(array_sum(array_column($data, 'artist_sum')), 4));
            $workSheet->setCellValue('E' . $i, round(array_sum(array_column($data, 'label_sum')), 4));
            $workSheet->getStyle('C'. $i . ':E' . $i)->getFont()->setBold(true);
            //$workSheet->getStyle('D'. (count($tempData)))->getFont()->setBold(true);
            //$workSheet->getStyle('E'. (count($tempData)))->getFont()->setBold(true);
        }

        $writer = new Xlsx($spreadSheet);
        $writer->save(self::$homePage .'xls/' .  $filename);

        $this->redirect("/xls/" . $filename);
    }



    private function getReportData(int $invoice_id, int $artist_id, string $groupBy = ''): \yii\db\DataReader|array
    {
        $query = "SELECT  
                    a.name as artist_name,
                    t.name as track_name,
                    t2p.percentage,
                    ii2.percentage as percentage_label,
                    o.name as prav1,
                    IFNULL(atu.name, a2ow.name) as prav2,
                    IFNULL(a_s.name, ari.platform) as platform,
                    ari.date_report,
                    ari.country,
                    c.currency_name";

            if (!empty($groupBy)) {
                $query .= ",  
                    sum(ari.count) as count,
                     sum(t2p.percentage / 100 * ari.amount) as amount";
            } else {
                $query .= ",  ari.count,
                    t2p.percentage / 100 * ari.amount as amount
                 ";
            }

            $query .= "
            FROM `invoice_items` ii
                    LEFT JOIN artist a ON a.id = ii.artist_id
                    LEFT JOIN invoice_items ii2 ON ii2.payment_invoice_id = ii.invoice_id
                    INNER JOIN track t ON REPLACE(t.isrc, '-', '') = REPLACE(ii2.isrc, '-', '') and ii.artist_id = t.artist_id
                    INNER JOIN invoice i ON i.invoice_id = ii2.invoice_id
                    LEFT JOIN currency c ON c.currency_id= i.currency_id
                    LEFT JOIN aggregator_report ar ON ar.id = i.aggregator_report_id
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
                    LEFT JOIN track_to_percentage t2p ON t2p.track_id = t.id and t2p.artist_id = a.id and t2p.ownership_type = a2ow.ownership_type_id 
                WHERE ii.artist_id = ii2.artist_id
                  AND t2p.percentage > 0
                  AND ii.invoice_id =:invoice_id
                  AND t.artist_id =:artist_id";

            if (!empty($groupBy)) {
                $query .= "
                GROUP BY ii.artist_id, t.id
                ORDER BY t.id ASC";
            } else {
                $query .= "
                ORDER BY t.id ASC, ari.`date_report` ASC";
            }

        return Yii::$app->db->createCommand($query)
            ->bindValue(':invoice_id', $invoice_id)
            ->bindValue(':artist_id', $artist_id)
            ->queryAll();
    }

    public function actionReport()
    {
        $model = new InvoiceReport();

        $resultReport = [];
        $mapp = [
            'a.name as artist' => 'Артист',
            't.name as track' => 'Трек',
            'sum(ari2.amount) as amount' => 'Дохід',
            'sum(ari2.`count`) as `count`' => 'Перегляди',
            'sum(ari2.`count`) as `count`' => 'Перегляди',
        ];

        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            return ActiveForm::validate($model);
        }

        if ($model->load(Yii::$app->request->post())) {
            $invoice = implode(', ', $model->invoiceId);
            $data = implode(', ', $model->data);

            $header = [];

            foreach ($model->data as $row) {
                $header[] = $mapp[$row];
            }

            $resultReport[0] = $header;

            $query = "
                    SELECT {$data}
                    FROM (SELECT REPLACE(isrc, '-', '') as isrc, sum(amount) as amount, sum(`count`) as `count`
                          FROM aggregator_report_item ari
                          INNER JOIN invoice i ON i.aggregator_report_id = ari.report_id and i.invoice_id in ({$invoice})
                          GROUP BY isrc, report_id
                         ) as ari2
                    LEFT JOIN track t ON REPLACE(t.isrc, '-', '') = ari2.isrc
                    LEFT JOIN artist a ON a.id = t.artist_id
                    WHERE a.label_id = 0
                    GROUP BY {$model->groupBy}
                    ORDER BY {$model->orderBy}
                    limit {$model->limit}";

            $request = Yii::$app->db->createCommand($query);

            foreach ($request->queryAll() as $row) {
                $resultReport[] = $row;
            }
        }

        return $this->render('report', [
            'invoice' => Invoice::find()
                ->select(["CONCAT(invoice.invoice_id, ' - ', aggregator.name, ' ', invoice.quarter, 'кв. ', invoice.year, 'р. (', invoice.description, ')')",
                    'invoice.invoice_id'
                ])->leftJoin('aggregator', 'aggregator.aggregator_id = invoice.aggregator_id')
                ->andFilterWhere(['invoice.invoice_type' => 1, 'invoice.invoice_status_id' => 2])
                ->orderBy('invoice.invoice_id DESC')
               // ->limit(10)
               ->indexBy('invoice.invoice_id')
                ->column(),
            'model' => $model,
            'report' => $resultReport,
            'payInvoiceReport' => new PayInvoiceReport(),
        ]);
    }
    
    /**
     * Звіт по виплатам
     * @return \yii\web\Response
     * @throws Exception
     */
    public function actionReportPay()
    {
        $model = new PayInvoiceReport();
        $model->load(Yii::$app->request->post());
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        if (Yii::$app->request->isAjax) {
          return ActiveForm::validate($model);
        }
        
        if (!empty($model->invoiceId)) {
            $invoice = Invoice::findOne($model->invoiceId);
            
            $model->quarter = $invoice->quarter;
            $model->year = $invoice->year;
        }
        
        $sql = "SELECT i.invoice_id,
                   sl.name as label_name,
                   a.name as artist,
                   a.full_name as full_name,
                   GROUP_CONCAT(distinct(ag.name)) as aggregator,
                    i2.year as year_pay,
                    i2.quarter as quarter_pay,
                    IFNULL(ar.year, i.year) as year_in,
                    IFNULL(ar.quarter, i.quarter) as quarter_in,
                    sum(ii.amount) as sum_pay,
                    c.currency_name
               FROM `invoice_items` ii
                    inner join invoice i ON i.invoice_id = ii.invoice_id and i.invoice_status_id = 2 and i.invoice_type != 2
                    inner join artist a ON a.id = ii.artist_id #and a.label_id = 0
                    left join aggregator_report ar ON ar.id = i.aggregator_report_id
                    left join `invoice_items` ii2 ON ii2.invoice_id = ii.payment_invoice_id and ii.artist_id = ii2.artist_id
                    inner join invoice i2 ON i2.invoice_id = ii2.invoice_id and i2.invoice_type =2 and i2.invoice_status_id = 2
                    left join aggregator ag ON ag.aggregator_id = i.aggregator_id
                    left join currency c ON c.currency_id = i.currency_id
                    LEFT JOIN sub_label sl ON sl.id = i.label_id
               WHERE i.currency_id = i2.currency_id";
        
        if ($model->invoiceId) {
            $sql .= " and i2.invoice_id = {$model->invoiceId} ";
        } else {
            $sql .= " and i2.quarter = {$model->quarter}  and i2.year = {$model->year} ";
        }
        
        $sql .="  GROUP BY ii.artist_id, ag.internal_type, ar.year, ar.quarter, i.currency_id
         ORDER BY i2.invoice_id desc, ii.artist_id asc,  IFNULL(ar.year, i.year) asc, IFNULL(ar.quarter, i.quarter) asc, i.aggregator_id asc;
         ";
        
        $data = Yii::$app->db->createCommand($sql)
            ->queryAll();
        
        if (empty($data)) {
            Yii::$app->session->setFlash('error', 'Дані не знайдені');
            $this->redirect(['invoice/report']);
        }
        
        $tempData[] = [
            '№ інвойсу',
            'Лейбл',
            'Акртист',
            'ПІБ',
            'Агрегатор',
            'Рік виплати',
            'Квартал виплати',
            'Рік надходження',
            'Квартал надходження',
            'Сума виплати',
            'Валюта',
        ];
        
        $tempData = array_merge($tempData, $data);
        
        $spreadSheet = new Spreadsheet();
        // баланси
        $workSheet = $spreadSheet->getActiveSheet();
        $workSheet->setTitle('Звіт по виплатім');
        $workSheet->getStyle('A1:J1')->getAlignment()
            ->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workSheet->getStyle('A1:J1')->getFont()->setBold(true);
        
       // $workSheet->getColumnDimension('A1:J1')->setWidth(15);
        
        // зберегти баланс на першому аркуші
        $workSheet->fromArray($tempData);
        $filename = "report_pay_invoice.xlsx";
        $writer = new Xlsx($spreadSheet);
        $writer->save(self::$homePage . 'xls/' . $filename);
        
        $this->redirect("/xls/".$filename);
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
