<?php

namespace backend\controllers;

use backend\models\AggregatorReport;
use backend\models\AggregatorReportItem;
use backend\models\Artist;
use backend\models\Invoice;
use backend\models\InvoiceItems;
use backend\models\InvoiceItemsSearch;
use backend\models\InvoiceLog;
use backend\models\InvoiceLogType;
use backend\models\InvoiceSearch;
use backend\models\InvoiceStatus;
use backend\models\InvoiceType;
use backend\models\Track;
use backend\models\User;
use backend\models\UserBalance;
use backend\widgets\DateFormat;
use backend\widgets\Str;
use common\models\Mail;
use common\models\t;
use DateTime;
use kartik\mpdf\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yii;
use backend\models\SubLabel;
use backend\models\SubLabelSearch;
use yii\db\Exception;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * SubLabelController implements the CRUD actions for SubLabel model.
 */
class SubLabelController extends Controller
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
     * Lists all SubLabel models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new SubLabelSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single SubLabel model.
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
     * Creates a new SubLabel model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new SubLabel();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing SubLabel model.
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
     * Deletes an existing SubLabel model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    #region invoice
    public function actionInvoice()
    {
        $searchModel = new InvoiceSearch();
        $queryParams = Yii::$app->request->queryParams;

        if (empty($queryParams['InvoiceSearch']['label_id'])) {
            $queryParams['InvoiceSearch']['label_id'] = 999999;
        }
        $dataProvider = $searchModel->search($queryParams);

        return $this->render('invoice/index', [
            'invoiceSearchModel' => $searchModel,
            'invoiceDataProvider' => $dataProvider,
        ]);
    }

    public function actionInvoiceView($label_id, $id)
    {
        $searchModel = new InvoiceItemsSearch();
        $queryParams = Yii::$app->request->queryParams;
        $queryParams['InvoiceItemsSearch']['invoice_id'] = $id;
        $queryParams['InvoiceItemsSearch']['label_id'] = $label_id;

        $dataProvider = $searchModel->search($queryParams);

        $modelItems = new InvoiceItems();

        $query = new \yii\db\Query();
        $query->from('invoice_items')
            ->select('SUM(IF(artist_id != 0, `amount`, 0)) AS `total_artist`, SUM(amount) AS `total`')
            ->innerJoin('invoice', '`invoice`.`invoice_id` = `invoice_items`.`invoice_id` and `invoice`.`label_id` = :label_id')
            ->where(['invoice_items.invoice_id' => $id])
            ->addParams([':label_id' => $label_id]);
        if (isset($queryParams['InvoiceItemsSearch']['artist_id']) && $queryParams['InvoiceItemsSearch']['artist_id'] >= 0) {
            $query->andWhere(['artist_id' => $queryParams['InvoiceItemsSearch']['artist_id']]);
        }

        if (isset($queryParams['InvoiceItemsSearch']['track_id']) && $queryParams['InvoiceItemsSearch']['track_id'] >= 0) {
            $query->andWhere(['track_id' => $queryParams['InvoiceItemsSearch']['track_id']]);
        }

        $data = $query->one();

        return $this->render('invoice/view', [
            'sub_label' => $this->findModel($label_id),
            'model' => Invoice::findOne($id),
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'modelItems' => $modelItems,
            'total' => $data
        ]);
    }
	
	public function actionInvoiceDelete($label_id, $id)
	{
		$model = $this->findModelInvoice($id);
		
		if (!in_array($model->invoice_status_id, [InvoiceStatus::Generated, InvoiceStatus::Error])
			|| Yii::$app->user->id == 1
		) {
			if ($model->delete() !== false) {
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
				
				Artist::calculationDeposit();
			}
		} else {
			Yii::$app->session->setFlash('error', "Неможа видалити цей інвойст");
		}
		
		return $this->redirect(['invoice']);
	}
	
	public function actionInvoiceCalculate(int $label_id, int $id)
	{
		$model = $this->findModelInvoice($id);
		
		if (!$model->getInvoiceItems()->count()) {
			
			Yii::$app->session->setFlash('error', 'В інвойсі відсутні записи для розрахунку');
			
			return $this->redirect(['view', 'id' => $id]);
		}
		
		if ($model->invoice_type == InvoiceType::$credit
			&& $model->invoice_status_id == InvoiceStatus::Generated
		) { // Виплата
			
			// записати останній інвойс і дату випалит в память акртисту
            
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
            
			/*$artist = [];
			foreach ($model->getInvoiceItems()->all() as $item) {
				$artist[] = $item->artist_id;
			}
			
			if (count($artist) > 0) {
				$artist = implode(',', $artist);
				Yii::$app->db->createCommand(
					"UPDATE `invoice_items` ii
                            INNER JOIN invoice i ON i.invoice_id = ii.invoice_id
								and i.invoice_type = 1
								and i.invoice_status_id = 2
                         SET ii.`payment_invoice_id`= {$model->invoice_id}
                         WHERE ii.artist_id IN ($artist)
                            AND ii.payment_invoice_id is null
                            AND i.currency_id = {$model->currency_id}"
				)->execute();
			}*/
			
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
                            AND ari.isrc = ii.isrc
                   ")->execute();
		}
        
        $model->calculate();
        
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
		
		return $this->redirect(['invoice-view', 'label_id'=> $label_id, 'id' => $id]);
	}


    /**
     * Перерахунок суми інвойсу
     *
     * @param int $label_id
     * @param int $id
     * @return Response
     */
    public function actionInvoiceFixTotal(int $label_id, int $id)
    {
        $model = Invoice::findOne($id);

        if (!$model->getInvoiceItems()->count()) {

            Yii::$app->session->setFlash('error', 'В інвойсі відсутні записи для розрахунку');

            return $this->redirect(['invoice-view', 'label_id' => $label_id, 'id' => $id]);
        }

        $total_temp = $model->total;

        $model->calculate();

        if ($total_temp != $model->total) {
            Yii::$app->session->setFlash('success', 'Інвойс перераховано: ' . $total_temp . ' => ' . $model->total);
        } else {
            Yii::$app->session->setFlash('success', 'Інвойс корректний');
        }

        return $this->redirect(['invoice-view', 'label_id' => $label_id, 'id' => $id]);
    }

    /**
     * Перерахунок відсотків і суми інвойсу
     *
     * @param int $label_id
     * @param integer $id
     * @return mixed
     * @throws Exception
     */
    public function actionInvoiceReCalculate(int $label_id, int $id)
    {
        $model = Invoice::findOne($id);

        if (!$model->getInvoiceItems()->count()) {

            Yii::$app->session->setFlash('error', 'В інвойсі відсутні записи для розрахунку');

            return $this->redirect(['invoice-view', 'label_id' => $label_id, 'id' => $id]);
        }

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

                        return $this->redirect(['invoice-view', 'label_id' => $label_id, 'id' => $id]);
                    }
                }
            }
        }

        $model->calculate();

        if ($total_temp != $model->total) {
            Yii::$app->session->setFlash('success', 'Загальна сума інфойсу змінена: ' . $total_temp . ' => ' . $model->total);
        } else {
            Yii::$app->session->setFlash('success', 'Інвойс перераховано');
        }

        return $this->redirect(['invoice-view', 'label_id' => $label_id, 'id' => $id]);
    }

    public function actionInvoiceExportToPdfAct($label_id, $id, $redirect = true)
    {
        $model = $this->findModelInvoice($id);

        $date = new DateTime($model->date_pay);
        $name = Str::transliterate($model->label->name);
        $filename = $date->format('Y_m_d') . "_{$name}_act_invoice_{$model->invoice_id}.pdf";

        if (file_exists(self::$homePage . 'pdf/' . $filename)) {
			$this->redirect("/pdf/" . $filename);
        }

        $this->layout = 'pdf';
        $tracks = [];
		$groupBy = 'ii.artist_id, ii2.track_id';
		$artistCount = $model->getInvoiceItems()->count();
		$templateName = 'invoice/act';
		
		if ($artistCount > 200) {
			$groupBy = 'ii.artist_id';
			$templateName = 'invoice/act_artist';
		}
		
        foreach ($model->invoiceItems as $it) {
            $_tracks = $this->getReportData($model->invoice_id, $it->artist_id, $groupBy);

            if (!empty($_tracks) && is_array($_tracks)) {
                $tracks = array_merge($tracks, $_tracks);
            }
        }

        $quarterDate = DateFormat::getQuarterDate($model->quarter, $model->year);

        //$quarterDate['start'] = date('d.m.Y', strtotime($model->period_from));
        //$quarterDate['end'] = date('d.m.Y', strtotime($model->period_to));

        $content = $this->render(
			$templateName,
            [
                'model' => $model,
                'tracks' => $tracks,
                'quarterDate' => $quarterDate,
            ]
        );

        // echo $content; exit;

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
            //'defaultFontSize' => 0.2,
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

    public function actionInvoiceExportToExcelReport($label_id, $id, $redirect = true)
    {
        $model = $this->findModelInvoice($id);

       // $date = new DateTime($model->date_pay);
       // $name = Str::transliterate($model->label->name);

       // $filename = $date->format('Y_m_d') . "_{$name}_report_invoice_{$model->invoice_id}.xlsx";

        $invoiceIds = $this->getAllInvoiceInProgress($model, $model->invoice_status_id);
		sort($invoiceIds);
        $name = Str::transliterate($model->label->name) . "_" . implode('_', $invoiceIds);
        $filename = "report_{$name}_q{$model->quarter}_y{$model->year}.xlsx";
		
        if (file_exists(self::$homePage. 'xls/' .$filename)) {
            if ($redirect === false) {
                return;
            }
			$this->redirect("/xls/".$filename);
        }

        $spreadSheet = new Spreadsheet();
        $workSheet = $spreadSheet->getActiveSheet();
        $workSheet->setTitle('Звіт по акту');

        $workSheet->getStyle('A1:N1')->getAlignment()->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::HORIZONTAL_CENTER);

        $workSheet->getColumnDimension('A')->setWidth(6);
        $workSheet->getColumnDimension('B')->setWidth(15);
        $workSheet->getColumnDimension('C')->setWidth(15);
        $workSheet->getColumnDimension('D')->setWidth(13);
        $workSheet->getColumnDimension('E')->setWidth(11);
        $workSheet->getColumnDimension('F')->setWidth(12);
        $workSheet->getColumnDimension('G')->setWidth(15);
        $workSheet->getColumnDimension('H')->setWidth(15);
        $workSheet->getColumnDimension('I')->setWidth(8);
        $workSheet->getColumnDimension('J')->setWidth(11);
        $workSheet->getColumnDimension('K')->setWidth(14);
        $workSheet->getColumnDimension('L')->setWidth(14);
        $workSheet->getColumnDimension('M')->setWidth(14);
        $workSheet->getColumnDimension('N')->setWidth(15);

        $workSheet->getStyle('A1:N1')->getFont()->setBold(true);
        $workSheet->getRowDimension('1')->setRowHeight(100);

        $tempData = [];
        $tempData[] = [
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
		
		
        $sum = [];
        //$sum2 = ['USD'=>0, 'EUR'=>0, 'UAH'=>0];

        $tempData2 = [];
        $tempData2[] = [
            'Виконавець',
            'Сума Роялті',
            'Валюта',
        ];
		
		$temp3 = [];

        foreach ($invoiceIds as $invoiceId) {
            $_model = $invoiceId == $model->invoice_id ? $model : $this->findModelInvoice($invoiceId);
          
                $tracks = $this->getReportDataXls($_model->invoice_id);
                if (!empty($tracks)) {
					$sum[$_model->currency->currency_name] = array_sum(array_column($tracks, 'amount_2'));
					
					foreach ($tracks as $item) {
						if ($item['amount_2'] != 0) {
							$tempData[] = [
								$i,
								$item['artist_name'],
								rtrim($item['track_name'], '1'),
								$item['count'],
								$item['percentage'],
								$item['amount'],
								$item['percentage_label'],
								$item['amount_2'],
								$item['currency_name'],
								$item['prav1'],
								$item['prav2'],
								$item['platform'],
								$item['country'],
								DateFormat::datumUah2($item['date_report']),
							];
							$i++;
							
							if (!isset($temp3[$item['artist_id']])) {
								$temp3[$item['artist_id']] = [
									'artist_name' => $item['artist_name'],
									'amount' => 0,
									'currency_name' => $item['currency_name'],
								];
							}
							
							$temp3[$item['artist_id']]['amount'] += abs($item['amount_2']);
						}
					}
                }
				
			 foreach ($temp3 as $it) {
				 $tempData2[] = $it;
            }
			
			$temp3 = [];
        }

        $workSheet->fromArray($tempData);

        $j = $i+2;
        $workSheet->setCellValue('A' . $j, 'Всього:');
        $workSheet->getStyle('A'. $j)->getFont()->setBold(true);

        foreach ($sum as $key => $item) {
			if (empty($item)) {
				continue;
			}
			
            $temp = ++$j;
            $workSheet->setCellValue('A' . $temp, $key);
            $workSheet->setCellValue('B' . $temp, round($item, 2));
            $workSheet->getStyle('A'. $temp)->getFont()->setBold(true);
            $workSheet->getStyle('B'. $temp)->getFont()->setBold(true);
        }

        $spreadSheet->createSheet();
        $spreadSheet->setActiveSheetIndex(1);
        $workSheet = $spreadSheet->getActiveSheet();
        $workSheet->setTitle('Звіт по артистам');
        $workSheet->getColumnDimension('A')->setWidth(20);
        $workSheet->getColumnDimension('B')->setWidth(15);
        $workSheet->getColumnDimension('C')->setWidth(10);
        $workSheet->getStyle('A1:C1')->getFont()->setBold(true);
		

        // зберегти в файл дані артистів
        $workSheet->fromArray($tempData2);

        $i = count($tempData2);

        $j = $i+1;
        $workSheet->setCellValue('A' . $j, 'Всього:');
        $workSheet->getStyle('A'. $j)->getFont()->setBold(true);
		
        foreach ($sum as $key => $item) {
			if (empty($item)) {
				continue;
			}
			
            $temp = ++$j;
            $workSheet->setCellValue('A' . $temp, $key);
            $workSheet->setCellValue('B' . $temp, round($item, 2));
            $workSheet->getStyle('A'. $temp)->getFont()->setBold(true);
            $workSheet->getStyle('B'. $temp)->getFont()->setBold(true);
        }

        // переключитись на 1 аркуш
        $spreadSheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadSheet);
        $writer->save(self::$homePage. 'xls/' .$filename);

        if($redirect) {
            $this->redirect("/xls/" . $filename);
        }
    }

    public function actionInvoiceMail($id)
    {
        $attach = [];

        /* @var $model Invoice */
        $model = $this->findModelInvoice($id);

        if (empty($model->label->email)) {
            Yii::$app->session->setFlash('error', 'У сублейбу відстуній email');

            return $this->redirect(['sub-label/invoice']);
        } else if ($model->getNotified()) {
            Yii::$app->session->setFlash('error', 'Цьому сублейбу вже відпавлено повідомлення');

            return $this->redirect(['sub-label/invoice']);
        }

        // перевірка чи всі дані заповнені
      //  if($this->checkBeforeExport($model->label) !== true) {
        //    Yii::$app->session->setFlash('error', 'У сублейба не заповнені всі дані');
       //     return $this->redirect(['sub-label/invoice']);
       // }

        $invoiceIds = $this->getAllInvoiceInProgress($model, InvoiceStatus::InProgress, 1);
        sort($invoiceIds);
		$name = Str::transliterate($model->label->name) . "_" . implode('_', $invoiceIds);

        $zvitExcelFileName = "report_{$name}_q{$model->quarter}_y{$model->year}.xlsx";
        $excel =  self::$homePage . 'xls/' .$zvitExcelFileName;

        if (!file_exists($excel)) {
            $this->actionInvoiceExportToExcelReport($model->label_id, $model->invoice_id, false);
        }

        $attach[] = [$excel, ['fileName' => $zvitExcelFileName]];

           // $_model = $invoice_id == $id ? $model : $this->findModelInvoice($invoice_id);
           // $date = new DateTime($_model->date_pay);

            /*$actFileName = $date->format('Y_m_d') . "_{$name}_act_invoice_{$invoice_id}.pdf";
            $act = self::$homePage . 'pdf/' . $actFileName;

            if (!file_exists($act)) {
                $this->actionInvoiceExportToPdfAct($model->label_id, $invoice_id, false);
            }

            $attach[] = [$act, ['fileName' => $actFileName]];*/
            //$date->format('Y_m_d') . "_


        $mail = new Mail([
            'from' => ['reports@blackbeatsmusic.com' => 'Black Beats Reports'],
            'to' => [$model->label->email => $model->label->name],
            'subject' => "Black Beats | Royalty Report Q{$model->quarter} {$model->year}",
            'bcc' => 'gmmkam123@gmail.com',
            'replyTo' => 'reports@blackbeatsmusic.com',
            'view' => [
                'html' => 'invoicePaymentNotification-html',
            ],
            'params' => [
                'invoiceModel' => $model,
            ],
            'attach' => $attach,
        ]);

        if ($mail->send('Payment Notification', $model)) {
            foreach ($invoiceIds as $invoice_id) {
                InvoiceLog::add($invoice_id, InvoiceLogType::EMAIL);
            }

            Yii::$app->session->setFlash('success', "Сублейбу {$model->label->name} успішно відправлено акт і звіт!");
        } else {
            Yii::$app->session->setFlash('error', "Сублейбу {$model->label->name} не вдалось відправлено акт і звіт! Зверніться до адміністратора.");
        }

        return $this->redirect(['sub-label/invoice']);
    }

    public function actionInvoiceApprove($id)
    {
        $model = $this->findModelInvoice($id);

        if ($model->getApproved()) {
            return $this->redirect(['sub-label/invoice']);
        } /*else if (!$model->getNotified()) {
            Yii::$app->session->setFlash('error', 'Цьому артисту ще не відпавлено повідомлення');

            return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
        }*/

        if(InvoiceLog::add($model->invoice_id, InvoiceLogType::APPROVED)) {
            $tId = User::getTelegramId(14); //  Тетяна бухгалтер

            if (!empty($tId)) {
                $message = "Підтверджено виплату суцблейбу {$model->label->name}.\n Інвойс {$model->invoice_id}";

                if (!empty($model->label->iban)) {
                    $message .= "\nIBAN {$model->label->iban}";
                }

                t::log($message, $tId);
            }
        }

        return $this->redirect(['sub-label/invoice']);
    }

    public function actionInvoicePay($id)
    {
        $model = $this->findModelInvoice($id);

        if ($model->getPayed()) {
            return $this->redirect(['sub-label/invoice']);
        } else if (!$model->getApproved()) {
            Yii::$app->session->setFlash('error', "Сублейб {$model->label->name} не підтвердив виплату");

            return $this->redirect(['sub-label/invoice']);
        }

        InvoiceLog::add($model->invoice_id, InvoiceLogType::PAYED);

        return $this->redirect(['sub-label/invoice']);
    }

    protected function findModelInvoice($id)
    {
        if (($model = Invoice::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
    private function checkBeforeExport(SubLabel $model)
    {
        switch ($model->label_type_id)
        {
            case '1': // ФІЗ

                if (empty($model->contract) || empty($model->full_name)) {
                    $error = "";

                    if (empty($model->full_name)) {
                        $error = "В сублейба {$model->name} не вкзано ФІО";
                    } else if (empty($model->contract)) {
                        $error = "В сублейба {$model->name} не вкзано № договору";
                    }

                    Yii::$app->session->setFlash('error', $error);

                    return false;
                }

                break;
            case '2': // ФОП
                if (empty($model->tov_name) || empty($model->full_name) || empty($model->contract) || empty($model->iban) ) {
                    $error = "";

                    if (empty($model->full_name)) {
                        $error = "В сублейба {$model->name} не вкзано ФІО";
                    } else if (empty($model->tov_name)) {
                        $error = "В сублейба {$model->name} не вкзано назву ТОВ";
                    } else if (empty($artist->contract)) {
                        $error = "В сублейба {$model->name} не вкзано № договору";
                    } else if (empty($artist->iban)) {
                        $error = "В сублейба {$model->name} не вкзано реквізити";
                    }

                    Yii::$app->session->setFlash('error', $error);

                    return false;
                }

                break;
        }

        return true;
    }
    private function getAllInvoiceInProgress(Invoice $invoice, int $statusId, ?int $logTypeId = null): array
    {
        $result = [$invoice->invoice_id];

        $temp = Yii::$app->db->createCommand("
            SELECT i.invoice_id
            FROM `invoice` as i
            	#LEFT JOIN invoice_log il ON il.invoice_id = i.invoice_id and il.log_type_id = {$logTypeId}
            WHERE i.invoice_status_id = {$statusId}
               AND i.invoice_type = 2 
               AND i.label_id = {$invoice->label_id}
               AND i.quarter = {$invoice->quarter}
               AND i.`year` = {$invoice->year}
               AND i.invoice_id !={$invoice->invoice_id}
               #AND il.log_type_id is null
            ORDER BY i.currency_id ASC")
            ->queryAll();

        foreach ($temp as $item) {
            $result[] = (int) $item['invoice_id'];
        }

        return $result;
    }

    private function getReportData(int $invoice_id, int $artist_id, string $groupBy = ''): \yii\db\DataReader|array
    {
        $query = "SELECT
					t.artist_name,
					t.name as track_name,
					100 as percentage,
					IFNULL(ii2.percentage, IF(ar.aggregator_id != 1, sl.percentage, sl.percentage_distribution)) as percentage_label,
					o.name as prav1,
					IFNULL(atu.name, a2ow.name) as prav2,
					IFNULL(a_s.name, ari.platform) as platform,
					ari.date_report,
					ari.country,
					c.currency_name,
                    sum(ari.count) as count,
                    ROUND(sum(ari.amount), 5) as amount,
                    ROUND(sum(ari.amount * IFNULL(ii2.percentage, IF(ar.aggregator_id != 1, sl.percentage, sl.percentage_distribution)) /100), 5) as amount_2
        FROM `invoice_items` ii
				INNER JOIN invoice_items ii2 ON ii2.payment_invoice_id = ii.invoice_id
				INNER JOIN invoice i ON i.invoice_id = ii2.invoice_id
				INNER JOIN aggregator_report ar ON ar.id = i.aggregator_report_id
				INNER JOIN aggregator_report_item ari ON ari.report_id = ar.id
					and ii2.track_id = ari.track_id
				LEFT JOIN artist a ON a.id = ii.artist_id
            	LEFT JOIN sub_label sl ON sl.id = a.label_id
				LEFT JOIN track t ON t.id = ii2.track_id
				LEFT JOIN currency c ON c.currency_id= i.currency_id
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
			WHERE ii.artist_id = ii2.artist_id
			  AND ii.invoice_id =:invoice_id
			  AND t.artist_id =:artist_id
				AND IFNULL(ii2.percentage, IF(ar.aggregator_id != 1, sl.percentage, sl.percentage_distribution)) > 0
		GROUP BY {$groupBy}
		HAVING amount_2 != 0
		ORDER BY ari.track_id ASC
		";
        

        return Yii::$app->db->createCommand($query)
            ->bindValue(':invoice_id', $invoice_id)
            ->bindValue(':artist_id', $artist_id)
            ->queryAll();
    }
	
	private function getReportDataXls(int $invoice_id): \yii\db\DataReader|array
	{
		$query = "SELECT
					ii.artist_id,
					t.artist_name,
					t.name as track_name,
					100 as percentage,
					IFNULL(ii2.percentage, IF(ar.aggregator_id != 1, sl.percentage, sl.percentage_distribution)) as percentage_label,
					o.name as prav1,
					IFNULL(atu.name, a2ow.name) as prav2,
					IFNULL(a_s.name, ari.platform) as platform,
					ari.date_report,
					ari.country,
					c.currency_name,
                    sum(ari.count) as count,
                    ROUND(sum(ari.amount), 5) as amount,
                    ROUND(sum(ari.amount * (IFNULL(ii2.percentage, IF(ar.aggregator_id != 1, sl.percentage, sl.percentage_distribution)) /100)), 5) as amount_2
             FROM `invoice_items` ii
				INNER JOIN invoice_items ii2 ON ii2.payment_invoice_id = ii.invoice_id
				INNER JOIN invoice i ON i.invoice_id = ii2.invoice_id
				INNER JOIN aggregator_report ar ON ar.id = i.aggregator_report_id
				INNER JOIN aggregator_report_item ari ON ari.report_id = ar.id
					and ii2.track_id = ari.track_id
				LEFT JOIN artist a ON a.id = ii.artist_id
				LEFT JOIN sub_label sl ON sl.id = a.label_id
				LEFT JOIN track t ON t.id = ii2.track_id
				LEFT JOIN currency c ON c.currency_id= i.currency_id
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
			WHERE ii.artist_id = ii2.artist_id
			  AND ii.invoice_id =:invoice_id
			  AND t.artist_id = ii.artist_id
			AND IFNULL(ii2.percentage, IF(ar.aggregator_id != 1, sl.percentage, sl.percentage_distribution)) > 0
		GROUP BY t.artist_id, ari.track_id, ari.platform, ari.country, ari.date_report
                HAVING amount_2 != 0
                ORDER BY ari.track_id ASC, ari.`date_report` ASC
        ";
		
		return Yii::$app->db->createCommand($query)
			->bindValue(':invoice_id', $invoice_id)
			->queryAll();
	}
    #endregion invoice

    /**
     * Finds the SubLabel model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return SubLabel the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = SubLabel::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
