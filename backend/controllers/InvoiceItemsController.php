<?php

namespace backend\controllers;

use backend\helpers\InvoiceAllocationService;
use backend\models\AggregatorReportItem;
use backend\services\ArtistPayoutReportService;
use backend\models\Artist;
use backend\models\ArtistLogType;
use backend\models\Currency;
use backend\models\Invoice;
use backend\models\InvoiceLog;
use backend\models\InvoiceLogType;
use backend\models\InvoiceStatus;
use backend\models\InvoiceType;
use backend\models\Track;
use backend\models\User;
use backend\models\UserBalance;
use backend\widgets\DateFormat;
use backend\widgets\Str;
use Box\Spout\Common\Entity\Style\Color;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use common\models\Mail;
use common\models\MailLog;
use common\models\t;
use kartik\mpdf\Pdf;
use PhpOffice\PhpSpreadsheet\Reader\Html;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Yii;
use backend\models\InvoiceItems;
use backend\models\InvoiceItemsSearch;
use yii\db\Exception;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

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
                    'recalculate-track' => ['POST'],
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
		
		
        if (in_array($model->invoice->invoice_type, [InvoiceType::$costs, InvoiceType::$advance]) // 3- витрати, 4 - баланс
			&& $model->amount > 0
		) {
            $model->amount = $model->amount * -1;
        } else if ($model->invoice->invoice_type == InvoiceType::$credit // 2- виплата
			&& $model->amount == 0
		) {
            $artist = Artist::findOne($model->artist_id);
            if (!is_null($artist)) {
				switch ($model->invoice->currency_id) {
					case Currency::EUR:
						if ($artist->deposit_1 > 0) {
							$model->amount = $artist->deposit_1 * -1;
						} else {
							Yii::$app->session->setFlash('error', 'Артист з мінуосвим депозитом EUR, не можна додати в інвойс на виплату.');
							
							return $this->redirect(['invoice/view', 'id' => $id]);
						}
						break;
					case Currency::UAH:
						if ($artist->deposit > 0) {
							$model->amount = $artist->deposit * -1;
						} else {
							Yii::$app->session->setFlash('error', 'Артист з мінуосвим депозитом UAH, не можна додати в інвойс на виплату.');
							
							return $this->redirect(['invoice/view', 'id' => $id]);
						}
						break;
					case Currency::USD:
						if ($artist->deposit_3 > 0) {
							$model->amount = $artist->deposit_3 * -1;
						} else {
							Yii::$app->session->setFlash('error', 'Артист з мінуосвим депозитом USD, не можна додати в інвойс на виплату.');
							
							return $this->redirect(['invoice/view', 'id' => $id]);
						}
						break;
				}
            } else {
				Yii::$app->session->setFlash('error', 'Артиста не знайдений.');
				return $this->redirect(['invoice/view', 'id' => $id]);
			}
        }

        if ($model->track_id && empty($model->isrc)) {
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
     * @throws Exception
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
        
        Yii::$app->db->beginTransaction();
        try {
            if ($model->delete() !== false) {
                $model->invoice->calculate();
                
                Yii::$app->db->createCommand(
                    "UPDATE aggregator_report_item ari
                            INNER JOIN track t ON t.id = ari.track_id and t.artist_id = {$model->artist_id}
                        SET ari.payment_invoice_id = null
                        WHERE ari.payment_invoice_id = {$model->invoice_id}"
                )->execute();
                
                Yii::$app->db->createCommand(
                    "UPDATE `invoice_items` ii
                        SET ii.`payment_invoice_id`= null
                         WHERE ii.`payment_invoice_id`= {$model->invoice_id}
                         AND ii.`artist_id`= {$model->artist_id}"
                )->execute();
                
                if (in_array($model->invoice->invoice_status_id, [2, 4]) // проведений або в процесі виплати
                    && in_array($model->invoice->invoice_type, [InvoiceType::$credit, InvoiceType::$costs, InvoiceType::$advance])
                ) {
                    Artist::calculationDeposit($model->artist_id);
                    
                    /** видаляємо розподілення для цього пункту виплати (payout item) */
                    InvoiceAllocationService::deleteAllocation($model->id);
                } else if ($model->invoice->invoice_type == InvoiceType::$debit) {
                    // видаляємо баланс юзера
                    UserBalance::deleteAll(['invoice_id' => $model->invoice_id, 'artist_id' => $model->artist_id]);
                }
            }
            Yii::$app->db->transaction->commit();
        } catch (\Throwable $e) {
            Yii::$app->db->transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Помилка при видаленні запису: ' . $e->getMessage());
            
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            
            return ['success' => false];
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
    

    /**
     * Finds the InvoiceItems model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return InvoiceItems the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     * @deprecated
     */
    public function actionPdfBalance(int $id, $redirect = true)
    {
        $model = $this->findModel($id);

        $date = new \DateTime($model->invoice->date_pay);
        $name = Str::transliterate($model->artist->name);
        $filename = $date->format('Y_m_d') . "_{$name}_balance_q{$model->invoice->quarter}_invoice_{$model->invoice->invoice_id}.pdf";

        $filePath = self::$homePage . 'pdf/' . $filename;
        clearstatcache(true, $filePath);
        if (file_exists($filePath)) {
            $this->redirect("/pdf/".$filename);
        }

        $this->layout = 'pdf';
        $vutraty = [];//$this->getVutraty($model->artist, $model->invoice->currency_id, $model->invoice);


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

    /**
     * @deprecated
     */
    public function actionExportBalance(int $id)
    {
        $model = $this->findModel($id);
        $date = new \DateTime($model->invoice->date_pay);
        $name = Str::transliterate($model->artist->name);
        $filename = $date->format('Y_m_d') . "_{$name}_balance_q{$model->invoice->quarter}_invoice_{$model->invoice->invoice_id}.xlsx";

        $filePath = self::$homePage . 'xls/' . $filename;
        clearstatcache(true, $filePath);
        if (file_exists($filePath)) {
            $this->redirect("/xls/".$filename);
        }

        $this->layout = 'pdf';

        $vutraty = [];// $this->getVutraty($model->artist, $model->invoice->currency_id, $model->invoice);

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
        $writer->save($filePath);

        $this->redirect("/xls/".$filename);
    }

    private function getActPdfName(int $id)
    {
        $model = $this->findModel($id);
        $date = new \DateTime($model->invoice->date_pay);
        $name = Str::transliterate($model->artist->name) . "_act_q{$model->invoice->quarter}_invoice_{$model->invoice->invoice_id}";


        if ($model->artist->type_id == 1) { // артисти
            if ($model->invoice->invoice_type == 2) { // для виплат збираємо всі валюти
                $invoiceItemsIds = $this->getAllInvoiceItemsForArtist($model, $model->invoice->invoice_status_id);
                $invoiceIds = $invoiceItemsIds['invoice'];
                sort($invoiceIds);
                $name = Str::transliterate($model->artist->name) . "_act_q{$model->invoice->quarter}_invoice_" . implode('_', $invoiceIds);
            }
        }

        return $date->format('Y_m_d') . "_{$name}.pdf";
    }

    public function actionPdfAct(int $id, $redirect = true, array $invoiceItemsIds = [])
    {
        $this->layout = 'pdf';
        $model = $this->findModel($id);
        
        // перевірка чи всі дані заповнені
        if($this->checkBeforeExport($model) !== true) {
           // Yii::$app->session->setFlash('error', 'У артиста не заповнені всі дані');
            
            return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
        }

        $invoice_id = $model->invoice_id;
        
        //$date = new \DateTime($model->invoice->date_pay);
        //$name = Str::transliterate($model->artist->name) . "_act_q{$model->invoice->quarter}_invoice_{$model->invoice->invoice_id}";
        $amount = 0;
        
        if ($model->artist->type_id == 1) { // артисти
            if ($model->invoice->invoice_type == 2) { // для виплат збираємо всі валюти
                $invoiceItemsIds = !empty($invoiceItemsIds) ? $invoiceItemsIds : $this->getAllInvoiceItemsForArtist($model, $model->invoice->invoice_status_id);
                $invoiceIds = $invoiceItemsIds['invoice'];
                sort($invoiceIds);
               // $name = Str::transliterate($model->artist->name) . "_act_q{$model->invoice->quarter}_invoice_" . implode('_', $invoiceIds);
                $invoice_id = implode('-', $invoiceIds);
                
                foreach ($invoiceItemsIds['items'] as $id) {
                    $_model = ($id == $model->id) ? $model : $this->findModel($id);
                    $amount += $_model->invoice->currency_id != 2 ? abs($_model->amount) * $_model->invoice->exchange : abs($_model->amount);
                }
            } else {
                $amount += $model->invoice->currency_id != 2 ? abs($model->amount) * $model->invoice->exchange : abs($model->amount);
            }
        } else {
            $amount += $model->invoice->currency_id != 2 ? abs($model->amount) * $model->invoice->exchange : abs($model->amount);
        }
        
        $filename = $this->getActPdfName($id);

		$filePath = self::$homePage . 'pdf/' . $filename;
        clearstatcache(true, $filePath);

        if (file_exists($filePath) && $redirect) {
           //$this->redirect("/pdf/".$filename);
            header("Location: /pdf/".$filename);
            exit;
        }

        $pdv = round($amount * 0.18, 2);
        $v_zbir = round($amount * 0.05, 2);
        
        $data = [
            'invoice_id' => $invoice_id,
            'date_pay' => $model->invoice->date_pay,
            'artist' => $model->artist,
            'amount' => $amount,
            'total' => ($amount - $pdv - $v_zbir),
            'pdv' => $pdv,
            'v_zbir' => $v_zbir,
            'quarterDate' => DateFormat::getQuarterDate($model->invoice->quarter, $model->invoice->year),
        ];
        
        $content = $this->render(
            'pdf/act',
            $data
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
        $pdf->filename = $filePath;
        // return the pdf output as per the destination setting
        $pdf->render();

        if ($redirect) {
            //$this->redirect("/pdf/".$filename);
            header("Location: /pdf/".$filename);
            exit;
        }
        
        return $filename;
    }

    public function actionExportAct(int $id, bool $redirect = true, array $invoiceItemsIds = [])
    {
        $model = $this->findModel($id);

        $invoiceItemsIds = !empty($invoiceItemsIds) ? $invoiceItemsIds : $this->getAllInvoiceItemsForArtist($model, $model->invoice->invoice_status_id);
		$invoiceIds = $invoiceItemsIds['invoice'];
		sort($invoiceIds);

        $name = Str::transliterate($model->artist->name) . "_" . implode('_', $invoiceIds);
        $filename = "report_{$name}_q{$model->invoice->quarter}_year_{$model->invoice->year}.xlsx";
        $filePath = self::$homePage . 'xls/' . $filename;
        // Force fresh export each time and avoid stale file_exists/stat cache behavior.
        clearstatcache(true, $filePath);

        if (file_exists($filePath) === true) {
            if ($redirect === false) {
                return $filename;
            }

            header("Location: /xls/".$filename);
            exit;
        }
        
        if ($model->artist->type_id == 1) {
            $spreadSheet = $this->generateReportInternalArtist($model, $invoiceItemsIds);
        } else {
            if (false/*$model->artist_id == 2227*/) {
                $this->generateReportPartnerSpout($model, $invoiceItemsIds, $filename);
                if ($redirect) {
                    header("Location: /xls/".$filename);
                    exit;
                }

                return $filename;
            } else {
                $spreadSheet = $this->generateReportPartner($model, $invoiceItemsIds);
            }
        }
        
        #endregion Звіт

        $writer = new Xlsx($spreadSheet);
        $writer->setUseDiskCaching(true);

        $writer->save($filePath);

        if ($redirect) {
            header("Location: /xls/".$filename);
            exit;
        }
        
        return $filename;
    }

    public function actionMail($id)
    {
        $model = $this->findModel($id);

        if (empty($model->artist->email)) {
            Yii::$app->session->setFlash('error', 'У артиста відстуній email');

            return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
        } /*else if ($model->getNotified()) {
            Yii::$app->session->setFlash('error', 'Цьому артисту вже відпавлено повідомлення');

            return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
        }*/
        // перевірка чи всі дані заповнені
       // if($this->checkBeforeExport($model) !== true) {
         //   Yii::$app->session->setFlash('error', 'У артиста не заповнені всі дані');
       //     return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
       // }

        $attach = [];
        $invoiceItemsIds = $this->getAllInvoiceItemsForArtist($model, InvoiceStatus::InProgress);
		
		$invoiceIds = $invoiceItemsIds['invoice'];
		sort($invoiceIds);
		//$name = Str::transliterate($model->artist->name) . "_" . implode('_', $invoiceIds);
		
        //$date = new \DateTime($model->invoice->date_pay);
        //$name = Str::transliterate($model->artist->name);

        //$actFileName = $date->format('Y_m_d') . "_{$name}_act_q{$model->invoice->quarter}_invoice_{$model->invoice->invoice_id}.pdf";
       /* $actFileName = $date->format('Y_m_d') . "_{$name}_act_q{$model->invoice->quarter}_invoice_{$model->invoice->invoice_id}.pdf";
        $act = self::$homePage . 'pdf/' . $actFileName;

         if (!file_exists($act)) {
             $this->actionPdfAct($id, false);
         }*/

        //$balanceFileName = $date->format('Y_m_d') . "_{$name}_balance_q{$model->invoice->quarter}_invoice_{$model->invoice->invoice_id}.pdf";
        //$reportFileName = "report_{$name}_q{$model->invoice->quarter}_year_{$model->invoice->year}.xlsx";
       // $excel =  self::$homePage .  'xls/' .$reportFileName;
        
        $reportFileName = $this->actionExportAct($id, false, $invoiceItemsIds);
        
        $excel =  self::$homePage .  'xls/' .$reportFileName;

      //  if (!file_exists($excel)) {
         //   $this->actionExportAct($id, false, $invoiceItemsIds);
      //  }

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
            throw new \RuntimeException("Артисту {$model->artist->name} не вдалось відправлено звіт! Зверніться до адміністратора.");
          //  Yii::$app->session->setFlash('error', "Артисту {$model->artist->name} не вдалось відправлено звіт! Зверніться до адміністратора.");
        }
        
        $logs = $model->invoice->getInvoiceLogs(InvoiceLogType::EMAIL)->all();
        
        $logs = array_filter($logs, function($log) use ($model) {
            return $log->artist_id == $model->artist_id;
        });
        
        $titleLog = "Відправлено на email: {$model->artist->email} в такі дати:\n";
        
        foreach ($logs as $log) {
            $titleLog .= date('d.m.Y H:i:s', strtotime($log->date_added)) . "\n";
        }
        
        return '<span class="glyphicon glyphicon-ok text-success" data-toggle="tooltip" data-placement="top" data-title=" ' . $titleLog. '"></span>';


       // return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
    }

    public function actionApprove($id)
    {
        $model = $this->findModel($id);

        if ($model->getApproved()) {
            return '<span class="glyphicon glyphicon-ok text-success"></span>';
         //   return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
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
        return '<span class="glyphicon glyphicon-ok text-success"></span>';
       // return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
    }

    public function actionPay($id)
    {
        $model = $this->findModel($id);

        if ($model->getPayed()) {
            return '<span class="glyphicon glyphicon-ok text-success"></span>';
           // return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
        } else if (!$model->getApproved()) {
            Yii::$app->session->setFlash('error', 'Цей артист не підтвердив виплату');
            return '<span class="glyphicon glyphicon-ok text-success"></span>';
            //return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
        }

        InvoiceLog::add($model->invoice_id, InvoiceLogType::PAYED, $model->artist_id);
        return '<span class="glyphicon glyphicon-ok text-success"></span>';

        //return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
    }

    /**
     * Перерахунок одного треку в конкретному debit-інвойсі за поточними відсотками.
     *
     * @param int $invoiceId
     * @param int $trackId
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionRecalculateTrack(int $invoiceId, int $trackId): \yii\web\Response
    {
        $invoice = Invoice::findOne($invoiceId);
        if ($invoice === null) {
            throw new NotFoundHttpException('Інвойс не знайдено.');
        }

        if ($invoice->invoice_type !== InvoiceType::$debit) {
            Yii::$app->session->setFlash('error', 'Перерахунок треку доступний лише для інвойсів типу «Нарахування».');
            return $this->redirect(['invoice/view', 'id' => $invoiceId]);
        }

        if (empty($invoice->aggregator_report_id)) {
            Yii::$app->session->setFlash('error', 'Інвойс не прив\'язаний до звіту агрегатора.');
            return $this->redirect(['invoice/view', 'id' => $invoiceId]);
        }

        $track = Track::findOne($trackId);

        if ($track === null) {
            Yii::$app->session->setFlash('error', 'Трек не знайдено.');
            return $this->redirect(['invoice/view', 'id' => $invoiceId]);
        }

        // Сума з агрегаторського звіту для цього треку
        $reportAmount = (new \yii\db\Query())
            ->from(AggregatorReportItem::tableName())
            ->select('SUM(amount)')
            ->where(['report_id' => $invoice->aggregator_report_id, 'track_id' => $trackId])
            ->scalar();

        if ($reportAmount === null || $reportAmount === false) {
            Yii::$app->session->setFlash('error', 'Трек не знайдено у звіті агрегатора для цього інвойсу.');
            return $this->redirect(['invoice/view', 'id' => $invoiceId]);
        }

        $reportAmount = (float) $reportAmount;

        if (UserBalance::find()
            ->where(['invoice_id' => $invoiceId, 'artist_id' => $track->artist_id, 'is_pay' => 1])
            ->exists()
        ) {
            Yii::$app->session->setFlash('error', 'Неможна перерахувати трек, бо вже є виплачений UserBalance для цього інвойсу.');
            return $this->redirect(['invoice/view', 'id' => $invoiceId]);
        }

        if (InvoiceItems::find()
            ->where(['invoice_id' => $invoiceId, 'track_id' => $trackId])
            ->andWhere(['not', ['payment_invoice_id' => null]])
            ->exists()
        ) {
            Yii::$app->session->setFlash('error', 'Неможна перерахувати цей трек, по ньому вже є виплата.');
            return $this->redirect(['invoice/view', 'id' => $invoiceId]);
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            // Видаляємо розподілення для items цього треку
            $oldItemIds = (new \yii\db\Query())
                ->from(InvoiceItems::tableName())
                ->select('id')
                ->where(['invoice_id' => $invoiceId, 'track_id' => $trackId])
                ->column();

            if (!empty($oldItemIds)) {
                InvoiceAllocationService::deleteAllocation($oldItemIds);
            }

            // Видаляємо UserBalance для інвойсу (буде перераховано)
            UserBalance::deleteAll(['invoice_id' => $invoiceId, 'artist_id' => $track->artist_id, 'is_pay' => 0]);

            // Видаляємо старі items цього треку
            InvoiceItems::deleteAll(['invoice_id' => $invoiceId, 'track_id' => $trackId]);

            // Перераховуємо за поточними відсотками
            $calculation = $track->getCalculation($invoice->aggregator_id, $reportAmount);

            foreach ($calculation as $value) {
                $invoiceItem = new InvoiceItems();
                $invoiceItem->invoice_id        = $invoiceId;
                $invoiceItem->track_id          = $track->id;
                $invoiceItem->isrc              = $track->isrc;
                $invoiceItem->artist_id         = $value['artist_id'];
                $invoiceItem->date_item         = date('Y-m-d');
                $invoiceItem->percentage        = $value['percentage'];

                if (isset($value['artist_percentage'])) {
                    $invoiceItem->artist_percentage = $value['artist_percentage'];
                }

                if (!empty($value['from_artist_id'])) {
                    $invoiceItem->from_artist_id = $value['from_artist_id'];
                }

                $invoiceItem->amount = $value['amount'];

                if (!$invoiceItem->save()) {
                    throw new \RuntimeException(
                        'Помилка збереження: ' . current(current($invoiceItem->getErrors()))
                    );
                }
            }

            // Оновлюємо загальну суму інвойсу
            $invoice->calculate();

            // Requeue invoice for allocation rebuild after single-track recalculation.
            if ($invoice->allocated != 0) {
                $invoice->allocated = 0;
                $invoice->save(false, ['allocated']);
            }

            // Якщо статус Calculated — оновлюємо баланси користувачів
            if ($invoice->invoice_status_id == InvoiceStatus::Calculated) {
                $invoice->calculateUser();
            }

            // Перераховуємо депозити артистів
            Artist::calculationDeposit();

            $transaction->commit();
            Yii::$app->session->setFlash(
                'success',
                "Трек «{$track->name}» перераховано в інвойсі #{$invoiceId}"
            );
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Помилка при перерахунку: ' . $e->getMessage());
        }

        return $this->redirect(['invoice/view', 'id' => $invoiceId]);
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
    
    private function generateReportInternalArtist(InvoiceItems $model, array $invoiceItemsIds = [])
    {
        $spreadSheet = new Spreadsheet();
        #region Баланс
        $workSheet = $spreadSheet->getActiveSheet();
        $workSheet->setTitle('Баланс');
        $workSheet->getColumnDimension('A')->setWidth(40);
        $workSheet->getColumnDimension('B')->setWidth(12);
        $workSheet->getColumnDimension('C')->setWidth(12);
        $workSheet->getColumnDimension('D')->setWidth(12);
        $workSheet->getColumnDimension('E')->setWidth(12);
        $workSheet->getColumnDimension('F')->setWidth(12);
        $workSheet->getColumnDimension('G')->setWidth(12);
        
        $workSheet->getStyle('A1:G1')->getFont()->setBold(true);
        $workSheet->getStyle('A1:G1')
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('BFBFBF');;
        $workSheet->getStyle('A3:G3')->getFont()->setBold(true);
        $workSheet->getStyle('A14:G14')->getFont()->setBold(true);
        $workSheet->getStyle('A3:G3')
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB('BFBFBF');
        $workSheet->getStyle("A3:G3")
            ->getBorders()
            ->getOutline()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()
            ->setRGB('000000'); // чорний
        $workSheet->getStyle("A3:G3")
            ->getBorders()
            ->getInside()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()
            ->setRGB('A5A5A5'); // сірий
        $workSheet->getStyle("A14:G14")
            ->getBorders()
            ->getOutline()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()
            ->setRGB('000000'); // чорний
        
        $workSheet->getStyle("A3:G14")
            ->getBorders()
            ->getOutline()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()
            ->setRGB('000000'); // чорний
        
        $tempData = [];
        $tempData[0] = ['Звіт за ' . $model->invoice->quarter . ' кв. ' . $model->invoice->year . ', ' . $model->artist->name];  // 1
        $workSheet->getStyle("A1:G1")
            ->getBorders()
            ->getBottom()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()
            ->setRGB('000000'); // чорний
        
        $tempData[1] = []; // 2
        $tempData[2] = [ // 3
            0 => 'Фінансовий звіт',
            1 => 'Сума',
            2 => 'Валюта',
            3 => 'Сума',
            4 => 'Валюта',
            5 => 'Сума',
            6 => 'Валюта',
        ];
        
        $i = 3;
        
         $sum = ['USD'=>0, 'EUR'=>0, 'UAH'=>0];//['USD'=>0, 'EUR'=>0, 'UAH'=>0];
         // $sum2 = ['USD'=>0, 'EUR'=>0, 'UAH'=>0];
        // $costs = [];
        $balance = [];
        //$sum = [];
        $curs = [
            'EUR' => 1,
            'UAH' => 1,
            'USD' => 1,
        ];
        
        foreach ($invoiceItemsIds['items'] as $id) {
            $_model = ($id == $model->id) ? $model : $this->findModel($id);
            $curs[$_model->invoice->currency->currency_name] = $_model->invoice->exchange;
            $sum[$_model->invoice->currency->currency_name] = round(($_model->amount < 0 ? $_model->amount * -1 : $_model->amount), 2);
            
            $balance[$_model->invoice->currency_id] = Artist::getLog(
                $_model->artist_id,
                $_model->invoice->quarter,
                $_model->invoice->year,
                $_model->invoice->currency_id,
                $_model->invoice->currency->currency_name,
                $_model->invoice_id
            );
        }

        $balanceKeys = array_keys($balance);

        $diff = array_diff([1, 2, 3], $balanceKeys);
        if ($diff) {
            foreach ($diff as $item) {
                $balance[$item] = Artist::getLog(
                    $model->artist_id,
                    $model->invoice->quarter,
                    $model->invoice->year,
                    $item,
                    ($item == 1 ? 'EUR' : ($item == 2 ? 'UAH' : 'USD')),
                );
            }
        }
        
        $cur = 1;
        
        if (!isset($balance[1])) {
            if (isset($balance[2])) {
                $cur = 2;
            } else {
                $cur = 3;
            }
        }
        foreach ($balance[$cur] as $key => $item) { // 1 - EURO
            $tempData[$i] = [
                0 => strip_tags($item['name']),
                1 => number_format($balance[1][$key]['value'] ?? 0, 2, '.', ''),
                2 => $balance[1][$key]['currency_name'] ?? 'EUR',
                3 => number_format($balance[3][$key]['value'] ?? 0, 2, '.', ''),
                4 => $balance[3][$key]['currency_name'] ?? 'USD',
                5 => number_format($balance[2][$key]['value'] ?? 0, 2, '.', ''),
                6 => $balance[2][$key]['currency_name'] ?? 'UAH',
            ];
            $i++; // 15
        }
        $income = $model->artist->getIncome($model->invoice->quarter, $model->invoice->year, $model->invoice->invoice_id);
        $costs = $model->artist->getCosts($model->invoice->quarter, $model->invoice->year);
        $nextQuarter = DateFormat::getNextQuarterYear($model->invoice->quarter, $model->invoice->year);
        $costs_next = $model->artist->getCosts($nextQuarter['quarter'], $nextQuarter['year']);

        if (count($income) + count($costs) + count($costs_next) > 0) {
            $i++;
            $tempData[$i] = []; // 15
            $i++; // 16
            $tempData[$i] = [// 16
                0 => 'Перелік фінансових операцій',
            ];
            $workSheet->getStyle('A' . $i)->getFont()->setBold(true);
            
            $i++; // 17
            
            $tempData[$i] = [
                0 => 'Назва',
                1 => 'Тип',
                2 => 'Сума',
                3 => 'Валюта',
                4 => 'Квартал',
                5 => 'Дата',
            ];
            
            $workSheet->getStyle("A$i:F$i")->getFont()->setBold(true);
            $workSheet->getStyle("A$i:F$i")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('BFBFBF');
            $workSheet->getStyle("A$i:F$i")
                ->getBorders()
                ->getInside()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()
                ->setRGB('A5A5A5'); // чорний
            
            $workSheet->getStyle("A$i:F$i")
                ->getBorders()
                ->getOutline()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()
                ->setRGB('000000'); // чорний
            
            $workSheet->getStyle("A$i:F" . ($i + count($income) + count($costs) + count($costs_next)))
                ->getBorders()
                ->getOutline()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()
                ->setRGB('000000'); // чорний
            $i++; // 18
            // доп. доходи
            foreach ($income as $item) {
                $tempData[$i] = [
                    0 => $item['description'],
                    1 => $item['invoice_type_name'] == 'Баланс' ? 'Нарахування' : $item['invoice_type_name'],
                    2 => number_format($item['amount'], 2, '.', ''),
                    3 => $item['currency_name'],
                    4 => $model->invoice->quarter . ' кв.',
                    5 => $item['date_item'],
                ];
                $i++;
            }
            // витрати
            foreach ($costs as $item) {
                $tempData[$i] = [
                    0 => $item['description'],
                    1 => $item['invoice_type_name'],
                    2 => number_format($item['amount'], 2, '.', ''),
                    3 => $item['currency_name'],
                    4 => $model->invoice->quarter . ' кв.',
                    5 => $item['date_item'],
                ];
                $i++;
            }
            // витрати за майбутній квартал
            foreach ($costs_next as $item) {
                $tempData[$i] = [
                    0 => $item['description'],
                    1 => $item['invoice_type_name'],
                    2 => number_format($item['amount'], 2, '.', ''),
                    3 => $item['currency_name'],
                    4 => $nextQuarter['quarter'] . ' кв.',
                    5 => $item['date_item'],
                ];
                $i++;
            }
        }
        
        // зберегти баланс на першому аркуші
        $workSheet->fromArray($tempData);
        
        $workSheet->setCellValue('I3', 'Валюта');
        $workSheet->setCellValue('J3', 'Сума');
        $workSheet->setCellValue('K3', 'Курс');
        $workSheet->getStyle('I3')->getFont()->setBold(true);
        $workSheet->getStyle('J3')->getFont()->setBold(true);
        $workSheet->getStyle('K3')->getFont()->setBold(true);
        $x = 4;
        
        foreach ($sum as $currency => $value) {
            if ($value > 0) {
                $workSheet->setCellValue('I' . $x, $currency);
                $workSheet->setCellValue('J' . $x, number_format($value, 4, '.', ''));
                
                if (isset($curs[$currency]) && $curs[$currency] != 1) {
                    $workSheet->setCellValue('K' . $x, number_format($curs[$currency], 4, '.', ''));
                }
                $x++;
            }
        }
        
        $workSheet->setSelectedCell('A1');
        #endregion Баланс
        
        #region Звіт
        $data = [];
        $data2 = [];
        foreach ($invoiceItemsIds['items'] as $id) {
            $_model = ($id == $model->id) ? $model : $this->findModel($id);
            $tracks = $this->getReportData($_model->invoice_id, $_model->artist_id);
            $curs[$_model->invoice->currency->currency_name] = $_model->invoice->exchange;
            
            if (!empty($tracks)) {
                $data = array_merge($data, $tracks);
                $sum2[$_model->invoice->currency->currency_name] = round(array_sum(array_column($tracks, 'amount_2')), 2);
            }

            $feats = $this->getReportDataFeat($_model->invoice_id, $_model->artist_id);
            
            if (!empty($feats)) {
                $sum2[$_model->invoice->currency->currency_name] += round(array_sum(array_column($feats, 'amount_2')), 2);
                $data = array_merge($data, $feats);
            }

            // 2 аркуш
            $tracks = $this->getReportData($_model->invoice_id, $_model->artist_id, true);

            if (!empty($tracks)) {
                $data2 = array_merge($data2, $tracks);
            }
            
            $feats = $this->getReportDataFeat($_model->invoice_id, $_model->artist_id, true);

            if (!empty($feats)) {
                //$sum2[$_model->invoice->currency->currency_name] += round(array_sum(array_column($feats, 'amount_2')), 2);
                $data2 = array_merge($data2, $feats);
            }

        }

        if ($diff) {
            foreach ($diff as $currency_id) {
                $tracks = $this->getReportDataNoPay($model->artist_id, $model->invoice->quarter, $model->invoice->year, $currency_id);
                if (!empty($tracks)) {
                    $data = array_merge($data, $tracks);
                    $sum2[($currency_id == 1 ? 'EUR' : ($currency_id == 2 ? 'UAH' : 'USD'))] = round(array_sum(array_column($tracks, 'amount_2')), 2);
                }

                // 2 аркуш
                $tracks = $this->getReportDataNoPay($model->artist_id, $model->invoice->quarter, $model->invoice->year, $currency_id, true);

                if (!empty($tracks)) {
                    $data2 = array_merge($data2, $tracks);
                }
            }
        }
        
        if (!empty($data2)) {
            $spreadSheet->createSheet();
            $spreadSheet->setActiveSheetIndex(1);
            $workSheet = $spreadSheet->getActiveSheet();
            $workSheet->setTitle('Звіт по трекам');
            
            $workSheet->getStyle('A1:I1')
                ->getAlignment()
                ->setWrapText(true)
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::HORIZONTAL_CENTER);
            
            $workSheet->getStyle("A1:I1")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('BFBFBF');
            $workSheet->getStyle("A1:I1")
                ->getBorders()
                ->getInside()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()
                ->setRGB('A5A5A5'); // чорний
            $workSheet->getStyle("A1:I1")
                ->getBorders()
                ->getOutline()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()
                ->setRGB('000000'); // чорний
            
            $workSheet->getStyle("A1:I" . (count($data2) + 1))
                ->getBorders()
                ->getOutline()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()
                ->setRGB('000000'); // чорний
            $workSheet->getColumnDimension('A')->setWidth(6);
            $workSheet->getColumnDimension('B')->setWidth(14);
            $workSheet->getColumnDimension('C')->setWidth(14);
            $workSheet->getColumnDimension('D')->setWidth(12);
            $workSheet->getColumnDimension('E')->setWidth(14);
            $workSheet->getColumnDimension('F')->setWidth(14);
            $workSheet->getColumnDimension('G')->setWidth(15);
            $workSheet->getColumnDimension('H')->setWidth(12);
            $workSheet->getColumnDimension('I')->setWidth(12);
            $workSheet->getStyle('A1:I1')->getFont()->setBold(true);
            
            $tempData = []; // порожній ряд
            $tempData[] = [
                '№',
                'Виконавець',
                'Назва твору',
                'Кіл-ть використань',
                'Частка авторських (суміжних) прав, %',
                'Загальна сума отриманої винагороди видавцем',
                'Ставка винагороди правовласника за авторські та суміжні права, %',
                'Сума Роялті',
                'Валюта',
            ];
            
            $i = 1;
            foreach ($data2 as $item) {
                $tempData[] = [
                    $i,
                    $item['artist_name'],
                    rtrim($item['track_name'], '12'),
                    $item['count'],
                    $item['percentage'],
                    $item['amount'],
                    $item['percentage_label'],
                    $item['amount_2'],
                    $item['currency_name'],
                ];
                $i++;
            }
            
            $workSheet->fromArray($tempData);
        }
        
        if (!empty($data)) {
            $spreadSheet->createSheet();
            $spreadSheet->setActiveSheetIndex(2);
            $workSheet = $spreadSheet->getActiveSheet();
            $workSheet->setTitle('Розгорнутий звіт');
            
            $workSheet->getStyle('A1:N1')->getAlignment()->setWrapText(true)
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::HORIZONTAL_CENTER);
            
            $workSheet->getStyle("A1:N1")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('BFBFBF');
            $workSheet->getStyle("A1:N1")
                ->getBorders()
                ->getInside()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()
                ->setRGB('A5A5A5'); // чорний
            $workSheet->getStyle("A1:N1")
                ->getBorders()
                ->getOutline()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()
                ->setRGB('000000'); // чорний
            
            $workSheet->getStyle("A1:N" . (count($data) + 1))
                ->getBorders()
                ->getOutline()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()
                ->setRGB('000000'); // чорний
            
            $workSheet->getColumnDimension('A')->setWidth(6);
            $workSheet->getColumnDimension('B')->setWidth(12);
            $workSheet->getColumnDimension('C')->setWidth(12);
            $workSheet->getColumnDimension('D')->setWidth(13);
            $workSheet->getColumnDimension('E')->setWidth(12);
            $workSheet->getColumnDimension('F')->setWidth(12);
            $workSheet->getColumnDimension('G')->setWidth(15);
            $workSheet->getColumnDimension('H')->setWidth(15);
            $workSheet->getColumnDimension('I')->setWidth(12);
            $workSheet->getColumnDimension('J')->setWidth(12);
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
            foreach ($data as $item) {
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
                    DateFormat::datumUah2($item['date_report'] ?? 'now'),
                ];
                $i++;
            }
            $workSheet->fromArray($tempData);
            
            // перевірка чи суми співпадають
            /*$error_sum = [];
            
            foreach ($sum as $key => $item) {
                if (round($item) != round($sum2[$key])) {
                    $error_sum[$key] = [
                        'invoice' => round($item, 2),
                        'report' => round($sum2[$key], 2)
                    ];
                }
            }
            if (!empty($error_sum)) {
                Yii::$app->session->setFlash('error', ['message' => 'Суми в інвойсі і в звіті не співпадають.', $error_sum]);
                return $this->redirect(['invoice/view', 'id' => $model->invoice_id, 'InvoiceItemsSearch'=>['artist_id' => $model->artist_id]]);
            }*/
            
            /*
            $q = $j+2;
            $workSheet->setCellValue('A' . $q, 'Сума Роялті правовласника:');
            $workSheet->getStyle('A'. $q)->getFont()->setBold(true);
            $workSheet->mergeCells("A{$q}:C{$q}");
            
            foreach ($sum2 as $key => $item) {
                 if(empty($item)) {
                     continue;
                 }
                $temp = ++$q;
                $workSheet->setCellValue('A' . $temp, $key);
                $workSheet->setCellValue('B' . $temp, round($item, 2));
                $workSheet->getStyle('A'. $temp)->getFont()->setBold(true);
                $workSheet->getStyle('B'. $temp)->getFont()->setBold(true);
            }*/
            
            // переключитись на 1 аркуш
            $spreadSheet->setActiveSheetIndex(0);
        }
        
        return $spreadSheet;
    }

    private function generateReportPartnerSpout(InvoiceItems $model, array $invoiceItemsIds = [], string $filename = '')
    {
        $filePath = self::$homePage . 'xls/' .$filename;

        $writer = WriterEntityFactory::createXLSXWriter();
        $writer->openToFile($filePath);

        // ======================
        // ✅ Sheet 1: Детальний звіт
        // ======================

        $headers = [
            '№', 'Виконавець', 'Назва Твору', 'Кіл-ть Використань',
            'Частка %', 'Сума', 'Ставка %',
            'Роялті', 'Валюта', 'Тип прав',
            'Тип використання', 'Платформа',
            'Країна', 'Період'
        ];

// ✅ стиль header
        $headerStyle = (new StyleBuilder())
            ->setFontBold()
            ->setFontSize(12)
            //->setBorder(\Box\Spout\Common\Entity\Style\Border::)
            //->setFontColor(Color::WHITE)
            //->setBackgroundColor(Color::rgb(0, 102, 204))
            ->build();


        $writer->addRow(
            WriterEntityFactory::createRowFromArray($headers, $headerStyle)
        );

        $invoices = Invoice::find()
            ->where(['in', 'invoice_id', $invoiceItemsIds['invoice']])
            ->indexBy('invoice_id')
            ->all();

        $reader = $this->getReportDataXlsSpout(
            $invoiceItemsIds['invoice'],
            $model->artist_id
        );

        $i = 1;
        $sum = [];
        $artistSums = [];

        while ($item = $reader->read()) {

            // ✅ пишемо рядок одразу
            $writer->addRow(
                WriterEntityFactory::createRowFromArray([
                    $i++,
                    $item['artist_name'],
                    rtrim($item['track_name'], '1'),
                    (int)$item['count'],
                    (int)$item['percentage'],
                    (double)$item['amount'],
                    (int)$item['percentage_label'],
                    (double)$item['amount_2'],
                    $item['currency_name'],
                    $item['prav1'],
                    $item['prav2'],
                    $item['platform'],
                    $item['country'],
                    DateFormat::datumUah2($item['date_report']),
                ])
            );

            // ✅ підсумки (малий масив — ок)
            $cur = $item['currency_name'];

            if (!isset($sum[$cur])) {
                $sum[$cur] = 0;
            }
            $sum[$cur] += $item['amount_2'];

            // ✅ агрегація по артисту
            $key = $item['artist_name'] . '|' . $cur;

            if (!isset($artistSums[$key])) {
                $artistSums[$key] = [
                    $item['artist_name'],
                    0,
                    $cur
                ];
            }

            $artistSums[$key][1] += abs($item['amount_2']);

            // ✅ debug памʼяті
            if ($i % 5000 === 0) {
                echo "Memory: " . round(memory_get_usage()/1024/1024, 2) . " MB\n";
            }
        }

        // ======================
        // ✅ Sheet 2: По артистам
        // ======================

        $writer->addNewSheetAndMakeItCurrent();

        $writer->addRow(
            WriterEntityFactory::createRowFromArray([
                'Виконавець', 'Сума Роялті', 'Валюта'
            ])
        );

        foreach ($artistSums as $row) {
            $writer->addRow(
                WriterEntityFactory::createRowFromArray($row)
            );
        }

        // ✅ підсумки
        foreach ($sum as $currency => $amount) {
            $writer->addRow(
                WriterEntityFactory::createRowFromArray([
                    'TOTAL', $amount, $currency
                ])
            );
        }

        $writer->close();


        $writer->close();

        header("Location: /xls/" . $filename);
        exit;


        return basename($filePath);
    }

    private function generateReportPartner(InvoiceItems $model, array $invoiceItemsIds = [])
    {
        $spreadSheet = new Spreadsheet();
        $workSheet = $spreadSheet->getActiveSheet();
        $workSheet->setTitle('Звіт по акту');
        
        // OPTIMIZATION: Column widths as array for batch setting
        $columnWidths = [
            'A' => 10, 'B' => 15, 'C' => 15, 'D' => 13, 'E' => 11, 'F' => 12,
            'G' => 15, 'H' => 15, 'I' => 8, 'J' => 11, 'K' => 14, 'L' => 14, 'M' => 14, 'N' => 15
        ];
        foreach ($columnWidths as $col => $width) {
            $workSheet->getColumnDimension($col)->setWidth($width);
        }

        $workSheet->getStyle('A1:N1')->getAlignment()->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::HORIZONTAL_CENTER);
        $workSheet->getStyle('A1:N1')->getFont()->setBold(true);
        $workSheet->getRowDimension('1')->setRowHeight(100);

        $headers = [
            '№', 'Виконавець', 'Назва Твору', 'Кіл-ть Використань', 'Частка авторських (суміжних) прав, %',
            'Загальна сума отриманої Винагороди Видавцем', 'Ставка Винагороди Правовласника за авторські та суміжні права, %',
            'Сума Роялті правовласника', 'Валюта', 'Вид прав', 'Тип використання', 'Тип та/або ресурс використання',
            'Країна', 'Період використання Об\'єкта',
        ];

        $workSheet->fromArray($headers, null, "A1");

        $sum = [];
        $artistSums = [];  // OPTIMIZATION: Flattened aggregation instead of nested array
        $curs = ['EUR' => 1, 'UAH' => 1, 'USD' => 1];

        // OPTIMIZATION: Preload all invoices with eager loading
        $invoices = Invoice::find()
            ->where(['in', 'invoice_id', $invoiceItemsIds['invoice']])
            ->indexBy('invoice_id')
            ->all();

        $payCurrency = [];

        foreach ($invoices as $inv) {
            $curs[$inv->currency->currency_name] = $inv->exchange;
            $payCurrency[] = $inv->currency_id;
        }

        // OPTIMIZATION: Batch insert rows instead of setCellValue per cell
        //$tracks = $this->getReportDataXls($invoiceItemsIds['invoice'], $model->artist_id);

        $reader = $this->getReportDataXlsSpout(
            $invoiceItemsIds['invoice'],
            $model->artist_id
        );

        $trackRows = [];
        $row = 1;

        while ($item = $reader->read()) {
            // Initialize currency sum if not set
            if (!isset($sum[$item['currency_name']])) {
                $sum[$item['currency_name']] = 0;
            }

            $sum[$item['currency_name']] += $item['amount_2'];

            // Build row array instead of individual setCellValue calls
            $trackRows[] = [
                $row,
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

            // OPTIMIZATION: Aggregate artist sums with simple key instead of nested array
            $key = $item['artist_name'] . '|' . $item['currency_name'];
            if (!isset($artistSums[$key])) {
                $artistSums[$key] = [
                    'artist_name' => $item['artist_name'],
                    'amount' => 0,
                    'currency_name' => $item['currency_name'],
                ];
            }
            $artistSums[$key]['amount'] += abs($item['amount_2']);
            $row++;
        }

        if (count($payCurrency) < 3) {
            $reader = $this->getReportDataXlsSpoutNoPay(
                $model->artist_id,
                $model->invoice->quarter,
                $model->invoice->year,
                $payCurrency
            );

            while ($item = $reader->read()) {
                // Initialize currency sum if not set
                if (!isset($sum[$item['currency_name']])) {
                    $sum[$item['currency_name']] = 0;
                }

                $sum[$item['currency_name']] += $item['amount_2'];

                // Build row array instead of individual setCellValue calls
                $trackRows[] = [
                    $row,
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

                // OPTIMIZATION: Aggregate artist sums with simple key instead of nested array
                $key = $item['artist_name'] . '|' . $item['currency_name'];
                if (!isset($artistSums[$key])) {
                    $artistSums[$key] = [
                        'artist_name' => $item['artist_name'],
                        'amount' => 0,
                        'currency_name' => $item['currency_name'],
                    ];
                }
                $artistSums[$key]['amount'] += abs($item['amount_2']);
                $row++;
            }
        }

        if ($trackRows) {
            // Batch insert all track rows
            $workSheet->fromArray($trackRows, null, "A2");

            $row = count($trackRows) + 2;

            // Add totals row
            $this->addTotalsRow($workSheet, $row, $sum, $curs);
        }

        //if (!empty($tracks)) {
           // $trackRows = [];
           // $row = 2;

           // foreach ($tracks as $idx => $item) {

          //  }

            // Batch insert all track rows
           // $workSheet->fromArray($trackRows, null, "A2");
            //$row = count($trackRows) + 2;

            // Add totals row
           // $this->addTotalsRow($workSheet, $row, $sum, $curs);
      //  }
        
        // OPTIMIZATION: Second sheet data
        $spreadSheet->createSheet();
        $spreadSheet->setActiveSheetIndex(1);
        $workSheet = $spreadSheet->getActiveSheet();
        $workSheet->setTitle('Звіт по артистам');
        $workSheet->getColumnDimension('A')->setWidth(20);
        $workSheet->getColumnDimension('B')->setWidth(15);
        $workSheet->getColumnDimension('C')->setWidth(10);
        $workSheet->getStyle('A1:C1')->getFont()->setBold(true);
        
        // Convert flat artist sums back to array format
        $tempData2 = [['Виконавець', 'Сума Роялті', 'Валюта']];
        foreach ($artistSums as $artistSum) {
            $tempData2[] = [$artistSum['artist_name'], $artistSum['amount'], $artistSum['currency_name']];
        }

        $workSheet->fromArray($tempData2);
        $totalRow = count($tempData2) + 2;

        // Add totals
        $this->addTotalsRow($workSheet, $totalRow, $sum, $curs);

        // переключитись на 1 аркуш
        $spreadSheet->setActiveSheetIndex(0);

        return $spreadSheet;
    }

    /**
     * OPTIMIZATION: Extract repeated totals row logic into helper method
     */
    private function addTotalsRow($workSheet, int $startRow, array $sum, array $curs): void
    {
        $j = $startRow;
        $workSheet->setCellValue('A' . $j, 'Всього:');
        $workSheet->getStyle('A' . $j)->getFont()->setBold(true);
        ++$j;

        $workSheet->setCellValue('A' . $j, 'Валюта');
        $workSheet->setCellValue('B' . $j, 'Сума');
        $workSheet->setCellValue('C' . $j, 'Курс');
        foreach (['A', 'B', 'C'] as $col) {
            $workSheet->getStyle($col . $j)->getFont()->setBold(true);
        }

        ++$j;
        foreach ($sum as $key => $amount) {
            if (!empty($amount)) {
                $workSheet->setCellValue('A' . $j, $key);
                $workSheet->setCellValue('B' . $j, round($amount, 4));
                if (isset($curs[$key]) && $curs[$key] != 1) {
                    $workSheet->setCellValue('C' . $j, round($curs[$key], 4));
                }
                ++$j;
            }
        }
    }

    private function getReportDataXlsSpout($invoiceIds, int $artist_id): \yii\db\DataReader|array
    {
        if (!is_array($invoiceIds)) {
            $invoiceIds = [$invoiceIds];
        }

        $invoiceIds = implode(',', $invoiceIds);

        $query = "SELECT qq.*,
                    o.name as prav1,
                    COALESCE(atu.name, a2ow.name) as prav2,
                    COALESCE(a_s.name, qq.p3) as platform,
                    c.currency_name
                    FROM (SELECT  t.artist_name as artist_name,
                    t.name as track_name,
                    ii2.artist_percentage as percentage,
                  	ii2.percentage as percentage_label,
                    ari.platform as p3,
                    ari.date_report,
                    ari.country,
      				ari.track_id,
            	sum(ari.count) as count,
               	ROUND(sum(ari.amount), 5) as amount,
               	ROUND(sum(ari.amount * ii2.percentage / 100), 5) as amount_2,
                
                ar.aggregator_id,
                i.currency_id
                
			FROM `invoice_items` ii
                INNER JOIN invoice_items ii2 ON ii2.payment_invoice_id = ii.invoice_id and ii.artist_id = ii2.artist_id
				INNER JOIN invoice i ON i.invoice_id = ii2.invoice_id
				
				INNER JOIN aggregator_report ar ON ar.id = i.aggregator_report_id
					and ar.report_status_id = 2
				INNER JOIN aggregator_report_item ari ON ari.report_id = ar.id
					and ii2.track_id = ari.track_id
				INNER JOIN track t ON t.id = ii2.track_id
					and ii.artist_id = t.artist_id
				
                WHERE ii.invoice_id in ($invoiceIds)
                  AND ii2.artist_id = :artist_id
                   GROUP BY ari.track_id, ari.platform, ari.country, ari.date_report
              ) as qq
                LEFT JOIN currency c ON c.currency_id= qq.currency_id
    
				LEFT JOIN aggregator agg ON agg.aggregator_id = qq.aggregator_id
				LEFT JOIN aggregator_type_use atu ON atu.type_id = agg.type_use_id
				LEFT JOIN aggregator_service a_s ON a_s.service_id = agg.service_id
                LEFT JOIN ownership o ON o.id = agg.ownership_type
                
				LEFT JOIN (
					SELECT aggregator_id, GROUP_CONCAT(ot_.name) as name
					FROM aggregator_to_ownership_type
						INNER JOIN ownership_type ot_ ON ot_.id = ownership_type_id
					GROUP BY aggregator_id
				) as a2ow ON a2ow.aggregator_id = agg.aggregator_id
        WHERE qq.amount_2 <> 0
        ORDER BY qq.currency_id ASC, qq.track_id ASC, qq.date_report ASC";

        return Yii::$app->db->createCommand($query)
            //  ->bindValue(':invoice_id', $invoice_id)
            ->bindValue(':artist_id', $artist_id)
            ->query();
    }

    /**
     * @throws Exception
     */
    private function getReportDataXlsSpoutNoPay(int $artist_id, int $quarter, int $year, array $payCurrency): \yii\db\DataReader|array
    {
        $currencyIds = implode(',', $payCurrency);
        $query = "SELECT IF(a.id != a2.id, CONCAT(a.name, ' (', a2.name, ')'), t.artist_name)as artist_name,
                    t.name as track_name,
                    ii.artist_percentage as percentage,
                  	ii.percentage as percentage_label,
                    o.name as prav1,
                    IFNULL(atu.name, a2ow.name) as prav2,
                    IFNULL(a_s.name, ari.platform) as platform,
                    ari.date_report,
                    ari.country,
                    c.currency_name,
                    ari.count,
             	ROUND((ii.artist_percentage / 100 * ari.amount), 4) as amount,
             	ROUND((ii.artist_percentage / 100 * ari.amount) * (ii.percentage / 100), 4) as amount_2
            FROM `invoice_items` ii
            INNER JOIN invoice i ON i.invoice_id = ii.invoice_id and i.invoice_type = 1 and i.invoice_status_id = 2
            INNER JOIN aggregator_report_item ari ON ii.track_id = ari.track_id #and ari.payment_invoice_id is null
            inner join aggregator_report ar ON ar.id = ari.report_id and ar.report_status_id = 2 and ar.id = i.aggregator_report_id
            inner join aggregator agg ON agg.aggregator_id = ar.aggregator_id and agg.currency_id = i.currency_id
            INNER JOIN track t ON t.id = ii.track_id #and ii.artist_id = t.artist_id
            LEFT JOIN artist a ON a.id = ii.artist_id
            LEFT JOIN artist a2 ON a2.id = t.artist_id
            LEFT JOIN currency c ON c.currency_id= i.currency_id
            LEFT JOIN aggregator_type_use atu ON atu.type_id = agg.type_use_id
			LEFT JOIN aggregator_service a_s ON a_s.service_id = agg.service_id
            LEFT JOIN (
                SELECT aggregator_id, ownership_type_id , GROUP_CONCAT(ot_.name) as name
                FROM aggregator_to_ownership_type
                    LEFT JOIN ownership_type ot_ ON ot_.id = ownership_type_id
                GROUP BY aggregator_id
            ) as a2ow ON a2ow.aggregator_id = agg.aggregator_id
            LEFT JOIN ownership o ON o.id = agg.ownership_type
            WHERE ii.payment_invoice_id is null
            and ii.artist_id = :artist_id
            and i.quarter = :quarter
            and i.year = :year
            and i.currency_id not in ($currencyIds)
            HAVING amount_2 <> 0
            ORDER BY i.currency_id ASC, ari.track_id ASC, ari.date_report ASC
            ";

        return Yii::$app->db->createCommand($query)
            ->bindValues([
                ':artist_id' => $artist_id,
                ':quarter' => $quarter,
                ':year' => $year
            ])
            ->query();
    }

    private function getReportDataXls($invoiceIds, int $artist_id): \yii\db\DataReader|array
    {
        if (!is_array($invoiceIds)) {
            $invoiceIds = [$invoiceIds];
        }

        $invoiceIds = implode(',', $invoiceIds);

        $query = "SELECT qq.*,
                    o.name as prav1,
                    COALESCE(atu.name, a2ow.name) as prav2,
                    COALESCE(a_s.name, qq.p3) as platform,
                    c.currency_name
                    FROM (SELECT  t.artist_name as artist_name,
                    t.name as track_name,
                    ii2.artist_percentage as percentage,
                  	ii2.percentage as percentage_label,
                   ari.platform as p3,
                    ari.date_report,
                    ari.country,
      				ari.track_id,
            	sum(ari.count) as count,
               	ROUND(sum(ari.amount), 5) as amount,
               	ROUND(sum(ari.amount * ii2.percentage / 100), 5) as amount_2,
                
                ar.aggregator_id,
                i.currency_id
                
			FROM `invoice_items` ii
                INNER JOIN invoice_items ii2 ON ii2.payment_invoice_id = ii.invoice_id and ii.artist_id = ii2.artist_id
				INNER JOIN invoice i ON i.invoice_id = ii2.invoice_id
				
				INNER JOIN aggregator_report ar ON ar.id = i.aggregator_report_id
					and ar.report_status_id = 2
				INNER JOIN aggregator_report_item ari ON ari.report_id = ar.id
					and ii2.track_id = ari.track_id
				INNER JOIN track t ON t.id = ii2.track_id
					and ii.artist_id = t.artist_id
				
                WHERE ii.invoice_id in ($invoiceIds)
                  AND ii2.artist_id = :artist_id
                   GROUP BY ari.track_id, ari.platform, ari.country, ari.date_report
              ) as qq
                LEFT JOIN currency c ON c.currency_id= qq.currency_id
    
				LEFT JOIN aggregator agg ON agg.aggregator_id = qq.aggregator_id
				LEFT JOIN aggregator_type_use atu ON atu.type_id = agg.type_use_id
				LEFT JOIN aggregator_service a_s ON a_s.service_id = agg.service_id
                LEFT JOIN ownership o ON o.id = agg.ownership_type
                
				LEFT JOIN (
					SELECT aggregator_id, GROUP_CONCAT(ot_.name) as name
					FROM aggregator_to_ownership_type
						INNER JOIN ownership_type ot_ ON ot_.id = ownership_type_id
					GROUP BY aggregator_id
				) as a2ow ON a2ow.aggregator_id = agg.aggregator_id
        WHERE qq.amount_2 <> 0
        ORDER BY qq.currency_id ASC, qq.track_id ASC, qq.date_report ASC";
        
        return Yii::$app->db->createCommand($query)
          //  ->bindValue(':invoice_id', $invoice_id)
            ->bindValue(':artist_id', $artist_id)
            ->queryAll();
    }

    private function getReportDataNoPay(int $artist_id, int $quarter, int $year, int $currency_id, bool $groupBy = false)
    {
        $groupSelect = "
        ari.count,
             	ROUND((ii.artist_percentage / 100 * ari.amount), 4) as amount,
             	ROUND((ii.artist_percentage / 100 * ari.amount) * (ii.percentage / 100), 4) as amount_2";
        $groupByGroup = "
        HAVING amount_2 > 0
         ORDER BY ari.track_id ASC";

        if ($groupBy) {
            $groupSelect = "
            sum(ari.count) as count,
             	ROUND(sum((ii.artist_percentage / 100 * ari.amount)), 4) as amount,
             	ROUND(sum((ii.artist_percentage / 100 * ari.amount) * (ii.percentage / 100)), 4) as amount_2";
            $groupByGroup = "
            GROUP BY ari.track_id
             HAVING amount_2 > 0
             ORDER BY ari.track_id ASC";
        }

        return Yii::$app->db->createCommand(
            "SELECT IF(a.id != a2.id, CONCAT(a.name, ' (', a2.name, ')'), a.name)as artist_name,
                    t.name as track_name,
                    ii.artist_percentage as percentage,
                  	ii.percentage as percentage_label,
                    o.name as prav1,
                    IFNULL(atu.name, a2ow.name) as prav2,
                    IFNULL(a_s.name, ari.platform) as platform,
                    ari.date_report,
                    ari.country,
                    c.currency_name,
                    $groupSelect
            FROM `invoice_items` ii
            INNER JOIN invoice i ON i.invoice_id = ii.invoice_id and i.invoice_type = 1 and i.invoice_status_id = 2
            INNER JOIN aggregator_report_item ari ON ii.track_id = ari.track_id #and ari.payment_invoice_id is null
            inner join aggregator_report ar ON ar.id = ari.report_id and ar.report_status_id = 2 and ar.id = i.aggregator_report_id
            inner join aggregator agg ON agg.aggregator_id = ar.aggregator_id and agg.currency_id = i.currency_id
            INNER JOIN track t ON t.id = ii.track_id #and ii.artist_id = t.artist_id
            LEFT JOIN artist a ON a.id = ii.artist_id
            LEFT JOIN artist a2 ON a2.id = t.artist_id
            LEFT JOIN currency c ON c.currency_id= i.currency_id
            LEFT JOIN aggregator_type_use atu ON atu.type_id = agg.type_use_id
			LEFT JOIN aggregator_service a_s ON a_s.service_id = agg.service_id
            LEFT JOIN (
                SELECT aggregator_id, ownership_type_id , GROUP_CONCAT(ot_.name) as name
                FROM aggregator_to_ownership_type
                    LEFT JOIN ownership_type ot_ ON ot_.id = ownership_type_id
                GROUP BY aggregator_id
            ) as a2ow ON a2ow.aggregator_id = agg.aggregator_id
            LEFT JOIN ownership o ON o.id = agg.ownership_type
            WHERE ii.payment_invoice_id is not null
            and ii.artist_id = :artist_id
            and i.quarter = :quarter
            and i.year = :year
            and i.currency_id = :currency_id
            $groupByGroup
            ")
            ->bindValue(':artist_id', $artist_id)
            ->bindValue(':quarter', $quarter)
            ->bindValue(':year', $year)
            ->bindValue(':currency_id', $currency_id)
            ->queryAll();
    }
	
    private function getReportData(int $invoice_id, int $artist_id, bool $groupBy = false): \yii\db\DataReader|array
    {
        if ($groupBy) {
            $queryGroupBy = ",
            	sum(ari.count) as count,
               	ROUND(sum(IF(IFNULL(ii2.artist_percentage, t2p.percentage) != 100, IFNULL(ii2.artist_percentage, t2p.percentage)/ 100 * ari.amount, ari.amount)), 5) as amount,
               	ROUND(sum(IF(IFNULL(ii2.artist_percentage, t2p.percentage) != 100, IFNULL(ii2.artist_percentage, t2p.percentage) / 100 * ari.amount, ari.amount) * (IFNULL(ii2.percentage, t2p2.percentage) / 100)), 5) as amount_2
			";

            $queryGroupBy2 = "
             GROUP BY ari.track_id
             HAVING amount_2 > 0
             ORDER BY ari.track_id ASC
            ";
        } else {
            $queryGroupBy = ",
            	ari.count,
             	ROUND(IF(IFNULL(ii2.artist_percentage, t2p.percentage) != 100, IFNULL(ii2.artist_percentage, t2p.percentage) / 100 * ari.amount, ari.amount), 5) as amount,
                ROUND(IF(IFNULL(ii2.artist_percentage, t2p.percentage) != 100, IFNULL(ii2.artist_percentage, t2p.percentage) / 100 * ari.amount, ari.amount) * (IFNULL(ii2.percentage, t2p2.percentage) / 100), 5) as amount_2
             ";

            $queryGroupBy2 = "
            HAVING amount_2 > 0
            #GROUP BY ari.track_id, ari.platform, ari.country, ari.date_report
            ORDER BY ari.track_id ASC, ari.date_report ASC
        ";
        }

        $query = "SELECT  a.name as artist_name,
                    t.name as track_name,
                    IFNULL(ii2.artist_percentage, t2p.percentage) as percentage,
                  	IFNULL(ii2.percentage, t2p2.percentage) as percentage_label,
                    o.name as prav1,
                    IFNULL(atu.name, a2ow.name) as prav2,
                    IFNULL(a_s.name, ari.platform) as platform,
                    ari.date_report,
                    ari.country,
                    c.currency_name
                    $queryGroupBy
        	FROM `invoice_items` ii
        		INNER JOIN invoice i2 ON i2.invoice_id = ii.invoice_id
                INNER JOIN invoice_items ii2 ON ii2.payment_invoice_id = ii.invoice_id
				INNER JOIN invoice i ON i.invoice_id = ii2.invoice_id
				
				INNER JOIN aggregator_report ar ON ar.id = i.aggregator_report_id
					and ar.report_status_id = 2
				INNER JOIN aggregator_report_item ari ON ari.report_id = ar.id
					and ii2.track_id = ari.track_id
				INNER JOIN track t ON t.id = ii2.track_id
					and ii.artist_id = t.artist_id
				LEFT JOIN artist a ON a.id = ii.artist_id
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
				LEFT JOIN (
					SELECT 100 / count(a2ot.id) * SUM(t2p.percentage) / 100 AS percentage, a2ot.aggregator_id, t2p.track_id, t2p.artist_id
						FROM track_to_percentage t2p
						LEFT JOIN aggregator_to_ownership_type a2ot ON a2ot.ownership_type_id = t2p.ownership_type
					WHERE t2p.artist_id = :artist_id
					GROUP BY t2p.artist_id, t2p.track_id, a2ot.aggregator_id
				) as t2p ON t2p.aggregator_id = agg.aggregator_id
                        and t2p.artist_id = ii2.artist_id 
                        and t2p.track_id = t.id
                LEFT JOIN track_to_percentage t2p2 ON t2p2.track_id = t.id and t2p2.artist_id = t.artist_id and t2p2.ownership_type = 5
                WHERE ii.artist_id = ii2.artist_id
                  AND ii.invoice_id =:invoice_id
                  AND ii2.artist_id =:artist_id
                  and i2.quarter = i.quarter
                  and i2.year = i.year
                $queryGroupBy2
                  ";

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
                    IFNULL(ii2.artist_percentage, t2p.percentage) as percentage,
                    #ii2.percentage as percentage_label,
                    IFNULL(ii2.percentage, t2p2.percentage) as percentage_label,
                    o.name as prav1,
                    IFNULL(atu.name, a2ow.name) as prav2,
                    IFNULL(a_s.name, ari.platform) as platform,
                    ari.date_report,
                    ari.country,
                    c.currency_name";

        if ($groupBy) {
           $query .= ",
            	sum(ari.count) as count,
             ROUND(sum(IF(IFNULL(ii2.artist_percentage, t2p.percentage)  != 100, IFNULL(ii2.artist_percentage, t2p.percentage)  / 100 * ari.amount, ari.amount)), 5) as amount,
             ROUND(sum(IF(IFNULL(ii2.artist_percentage, t2p.percentage)  != 100, IFNULL(ii2.artist_percentage, t2p.percentage)  / 100 * ari.amount, ari.amount)) * (IFNULL(ii2.percentage, t2p2.percentage) / 100), 5) as amount_2
             ";
        } else {
			$query .= ",
            	ari.count,
             	ROUND(IF(IFNULL(ii2.artist_percentage, t2p.percentage) != 100, IFNULL(ii2.artist_percentage, t2p.percentage) / 100 * ari.amount, ari.amount), 5) as amount,
                ROUND(IF(IFNULL(ii2.artist_percentage, t2p.percentage) != 100, IFNULL(ii2.artist_percentage, t2p.percentage) / 100 * ari.amount, ari.amount) * (IFNULL(ii2.percentage, t2p2.percentage) / 100), 5) as amount_2
             ";
        }

        $query .= "
        FROM `invoice_items` ii
        			INNER JOIN invoice i2 ON i2.invoice_id = ii.invoice_id
                    INNER JOIN invoice_items ii2 ON ii2.payment_invoice_id = ii.invoice_id
                    INNER JOIN track t ON t.id = ii2.track_id and ii.artist_id != t.artist_id
                    INNER JOIN invoice i ON i.invoice_id = ii2.invoice_id
                   INNER JOIN aggregator_report ar ON ar.id = i.aggregator_report_id
                   		and ar.report_status_id = 2
                    LEFT JOIN artist a ON a.id = ii.artist_id
                    LEFT JOIN artist a2 ON a2.id = t.artist_id
                    LEFT JOIN currency c ON c.currency_id= i.currency_id
                    LEFT JOIN aggregator_report_item ari ON ari.report_id = ar.id
                    	and ii2.track_id = ari.track_id
                    	#and ari.amount > 0
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
                  		AND ii.invoice_id =:invoice_id
                  		AND ii2.artist_id =:artist_id
                   		and i2.quarter = i.quarter
                   		and i2.year = i.year
                  ";
		
		if ($groupBy) {
			$query .= "
				 GROUP BY ari.track_id
				 HAVING amount_2 > 0
				 ORDER BY ari.track_id ASC
            ";
		} else {
			$query .= "
				#GROUP BY ari.track_id, ari.platform, ari.country, ari.date_report
				HAVING amount_2 > 0
				ORDER BY ari.track_id ASC, ari.date_report ASC
        	";
		}
        

        return Yii::$app->db->createCommand($query)
            ->bindValue(':invoice_id', $invoice_id)
            ->bindValue(':artist_id', $artist_id)
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

    private function getAllInvoiceItemsForArtist(InvoiceItems $invoiceItem, ?int $statusId = null, ?int $logTypeId = null): array
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
		
		$q .= " ORDER BY i.currency_id ASC ";

        $temp = Yii::$app->db->createCommand($q)
            ->queryAll();

        foreach ($temp as $item) {
            $result['invoice'][] = (int) $item['invoice_id'];
            $result['items'][] = (int) $item['id'];
        }

        return $result;
    }
    
    #region test
    
    
    public function actionPdfAct2(int $id)
    {
        $invoice = InvoiceItems::findOne($id);
        
        if (!$invoice || $invoice->invoice_type !== 2) {
            throw new \yii\web\NotFoundHttpException('Invalid payout invoice');
        }
        
        $artistId = $invoice->artist_id;
        
        $service = new \backend\services\ArtistPayoutReportService();
        
        $file = $service->generateActPdf(
            payoutInvoiceId: $invoice->invoice_id,
            artistId: $invoice->artist_id
        );
        
        return Yii::$app->response->sendFile(
            $file,
            basename($file)
        );
    }
    
    #endregion test

}
