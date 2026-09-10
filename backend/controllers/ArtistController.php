<?php

namespace backend\controllers;

use aki\telegram\Telegram;
use backend\helpers\InvoiceService;
use backend\models\ArtistLog;
use backend\models\Invoice;
use backend\models\InvoiceItems;
use backend\models\InvoiceLog;
use backend\models\InvoiceLogType;
use backend\models\InvoiceStatus;
use backend\models\User;
use backend\models\VUnpaidIncomeByPeriodSearch;
use backend\widgets\DateFormat;
use backend\widgets\Str;
use common\models\Mail;
use PhpOffice\PhpSpreadsheet\Reader\Html;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yii;
use backend\models\Artist;
use backend\models\ArtistSearch;
use yii\db\Query;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use backend\models\Upload;
use yii\web\UploadedFile;
use yii\web\Response;
use yii\bootstrap\ActiveForm;
use yii\filters\AccessControl;
use yii\helpers\Url;

/**
 * ArtistController implements the CRUD actions for Artist model.
 */
class ArtistController extends Controller
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
            // Фільтр доступу
            'access' => [
                'class' => AccessControl::class,
                // 'only' => ['index', 'view', 'create', 'update', 'delete'], // перелік екшенів
                //  'denyCallback' => function ($rule, $action) {
                // Кастомна реакція на заборону
                //  throw new \yii\web\ForbiddenHttpException('Немає прав для цієї дії.');
                // },
                'rules' => [
                    // Гості можуть переглядати список та один запис
                    [
                        'allow' => true,
                        'actions' => ['index', 'view'],
                        'roles' => ['@'], // '?' – гість, '@' – автентифікований
                    ],
                    // Створення/оновлення тільки для залогінених
                    [
                        'allow' => true,
                        'actions' => ['view', 'create', 'update', 'modal', 'calculate-deposit',  'create-invoice', 'export-act', 'export-balance', 'export-artist', 'mail'],
                        'roles' => ['moder'],
                    ],
                    // Видалення лише для ролі 'admin'
                    [
                        'allow' => true,
                        'actions' => ['delete'],
                        'roles' => ['admin'], // RBAC роль/дозвіл
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all Artist models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ArtistSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'sumDepositUAH' => 0, //(new \yii\db\Query())->from(Artist::tableName())->where(['!=', 'id', 0])->sum('deposit'),
            'sumDepositEURO' => 0, //(new \yii\db\Query())->from(Artist::tableName())->where(['!=', 'id', 0])->sum('deposit_1'),
        ]);
    }

    /**
     * Displays a single Artist model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $searchModel = new VUnpaidIncomeByPeriodSearch();
        $searchModel->artist_id = $id;
        
        $dataProvider = $searchModel->search(
            Yii::$app->request->queryParams
        );
        
        
        return $this->render('view', [
            'model' => $this->findModel($id),
            'searchModelV'  => $searchModel,
            'dataProviderV' => $dataProvider,
        
        ]);
    }

    /**
     * Creates a new Artist model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Artist();

		if (Yii::$app->request->isAjax) {
			if ($model->load(Yii::$app->request->post())){
				Yii::$app->response->format = Response::FORMAT_JSON;

				return ActiveForm::validate($model);
			}
			return true;
		}

        $oldModel = $model->toArray();

		if ($model->load(Yii::$app->request->post())) {
            $id = Artist::find()->orderBy('id DESC')->one()->id;
            $id++;

           $file = UploadedFile::getInstance($model, 'file');

            if ($file && $file->tempName) {
                $model->file = $file;

                if ($model->validate(['file'])) {
                    $model->logo = Upload::createImage($model, $id, 'artist', [60, 60]);
                    
                }
            }

            if($model->validate() && $model->save()) {
                $model->saveLog($oldModel, $model->toArray());
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionModal()
    {
        $model = new Artist();

		if (Yii::$app->request->isAjax ) {
			if ($model->load(Yii::$app->request->post())){
				Yii::$app->response->format = Response::FORMAT_JSON;

				return ActiveForm::validate($model);
			}
			return true;
		}
		if ($model->load(Yii::$app->request->post())) {
          //  Yii::$app->response->format = Response::FORMAT_JSON;
           // $valid = ActiveForm::validate($model);
          //  if($valid){
              //   
          //       return $valid;
         //  }
            $id = Artist::find()->orderBy('id DESC')->one()->id;
             $id++;
           $file = UploadedFile::getInstance($model, 'file');
            if ($file && $file->tempName) {
                $model->file = $file;
                if ($model->validate(['file'])) {
                    $model->logo = Upload::createImage($model, $id, 'artist', [60, 60]);
                    
                }
            }
            if($model->validate() && $model->save()) {
                   // return $this->goBack();
                    return $this->redirect(['track/create']);
            }
            
              // }
        }
        
    }

    /**
     * Updates an existing Artist model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

		if (Yii::$app->request->isAjax ) {
			if ($model->load(Yii::$app->request->post())){
				Yii::$app->response->format = Response::FORMAT_JSON;

				return ActiveForm::validate($model);
			}
			return true;
		}
        
        $iban = $model->iban;
        
        $currentData = $model->toArray();
        if($model->load(Yii::$app->request->post())) {
            $file = UploadedFile::getInstance($model, 'file');

            if ($file && $file->tempName) {
               
                $model->file = $file;

                if ($model->validate(['file'])) {
                   $model->logo = Upload::updateImage($model, $model->logo, 'artist', [60, 60]);
                }
            }

            $model->iban = str_replace(' ', '', $model->iban);

             if($model->validate() && $model->save()) {
                 $model->saveLog($currentData, $model->toArray());

                 if ($iban != $model->iban) {
                     try {
                         /* @var $client Telegram */
                         $client = Yii::$app->telegram;
                         $data = [
                             'chat_id' => User::getTelegramId(14), // Тетяна бухгалтер,
                             'text' => "<b>Зміна IBAN</b>\n"
                                 . "Артист: {$model->name}\n"
                                 . "<s>{$iban}</s>\n{$model->iban}",
                             'parse_mode' => 'HTML',
                         ];
                         
                         $client->sendMessage($data);
                         $data['chat_id'] = 404070580; // temp
                         $client->sendMessage($data);
                     } catch (\Throwable) {}
                 }
                 
                 return $this->redirect(['view', 'id' => $model->id]);
             }
            
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Artist model.
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

    public function actionCalculateDeposit(?int $id = null, string $url = '')
    {
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            $res = [];
            $dep = Artist::calculationDeposit($id);

            if (isset($dep[$id])) {
                foreach ($dep[$id] as $currency => $item) {
                    $res[$currency] = $item['new'];
                }
            }

            return $res;
        } else {
           $errors = Artist::calculationDeposit();

           if (!empty($errors)) {
               Yii::$app->session->setFlash('error', "Оновлено депозити:");
               foreach ($errors as $error) {
                   Yii::$app->session->addFlash('error', $error);
               }
           }
        }

        return $this->redirect($url);
    }

    public function actionCreateInvoice()
    {
        $model = new Invoice();
        Yii::$app->response->format = Response::FORMAT_JSON;
        // Ajax-validate
        if (Yii::$app->request->isAjax
                && isset(Yii::$app->request->post()['ajax'])
                && $model->load(Yii::$app->request->post())
        ) {
           // Yii::$app->response->format = Response::FORMAT_JSON;
            return \yii\widgets\ActiveForm::validate($model);
        }
        
       // var_dump(Yii::$app->request->post());
        
       // echo 'DSfasd'; die;
        
        $artistIds = explode(',', Yii::$app->request->post('Invoice')['artist_ids'] ?? '');
        
        if (empty($artistIds) || !is_array($artistIds)) {
            return [
                'success' => false,
                'message' => 'Артисти не передані'
            ];
        }
        
        try {
            $invoiceId = InvoiceService::createPayFromArtists($artistIds, Yii::$app->request->post());
            
            return [
                'success' => true,
                'url' => Url::to(['invoice/view', 'id' => $invoiceId], true)
            ];
            
        } catch (\Throwable $e) {
            Yii::error($e);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
        
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            
            $model->description = 'Виплата за ' .$model->quarter . 'кв ' . $model->year;
            if ($model->save()) {
                $Invoice = Yii::$app->request->post('Invoice');
                $artist_ids = explode(',', $Invoice['artist_ids']);

                if ($model->currency_id == 1) { // EURO
                    $data = Artist::find()
                        ->select(['deposit_1', 'id'])
                        ->where(['in', 'id', $artist_ids])
                        ->andWhere(['>', 'deposit_1', 0])
                        ->indexBy('id')
                        ->column();
                } else  if ($model->currency_id == 3) {
                    $data = Artist::find()
                        ->select(['deposit_3', 'id'])
                        ->where(['in', 'id', $artist_ids])
                        ->andWhere(['>', 'deposit_3', 0])
                        ->indexBy('id')
                        ->column();
                } else {
                    $data = Artist::find()
                        ->select(['deposit', 'id'])
                        ->where(['in', 'id', $artist_ids])
                        ->andWhere(['>', 'deposit', 0])
                        ->indexBy('id')
                        ->column();
                }

                foreach ($data as $artist_id => $dep) {
                    $invoiceItem = new InvoiceItems();
                    $invoiceItem->invoice_id = $model->invoice_id;
                    $invoiceItem->artist_id = $artist_id;
                    $invoiceItem->amount = $dep * -1;
                    $invoiceItem->date_item = date('Y-m-d');

                    if (!$invoiceItem->save()) {
                        $model->delete();

                        $errors = $invoiceItem->getErrors();

                        Yii::$app->session->setFlash('error', 'Помилка додаваня запису в інвойс інвойсу на виплату: ' .current($errors));

                        return $this->redirect(['artist/index']);
                    }
                }

                $model->calculate();
                
                
                return [
                    'success' => true,
                    'url' => Url::to(['invoice/view', 'id' => $model->invoice_id], true),
                ];
                
               // return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
            }
            //   Yii::$app->session->setFlash('error', 'Не вдалось створити інвойс');
            return [
                'success' => false,
                'message' => 'Не вдалось створити інвойс',
            ];
         //   return $this->redirect(['artist/index']);
        }
        
        $errors = $model->getErrors();

       // Yii::$app->session->setFlash('error', 'Помилка сворення інвойсу на виплату: ' .current($errors));
        
        return [
            'success' => false,
            'message' => 'Помилка сворення інвойсу на виплату: ' .current($errors)
        ];
        

       // return $this->redirect(['artist/index']);
    }

    /**
     * @param int $id
     * @return void
     * @throws NotFoundHttpException
     * @throws \yii\db\Exception
     * @deprecated use actionExportAct
     */
    public function actionExportBalance(int $id)
    {
        $model = $this->findModel($id);

        $d = date('Y_m_d_i');
        $name = Str::transliterate($model->name);

        $filename = "/home/atpjwxlx/domains/blck.link/public_html/backend/web/balance_q3_{$d}_{$name}.xlsx";

        if (file_exists($filename)) {
            $this->redirect("/balance_q3_{$d}_{$name}.xlsx");
        }

        $balance = ArtistLog::find()
            ->where(['artist_id' => $id, 'quarter' => 3, 'currency_id' => 1])
            ->one(); // euro

        if (empty($balance->artist_id)) {
            $model->saveBalance(3, 1, 2024);
        }

        $balance = ArtistLog::find()
            ->where(['artist_id' => $id, 'quarter' => 3, 'currency_id' => 2])
            ->one(); // uah

        if (empty($balance->artist_id)) {
            $model->saveBalance(3, 2, 2024);
        }

        $this->layout = 'pdf';

        $all_euro = Yii::$app->db->createCommand(
            "SELECT alt.name, `al`.`sum`, c.currency_name
                                    FROM `artist_log` `al` 
                                    INNER JOIN artist_log_type alt ON alt.log_type_id = `al`.`type_id` 
                                    INNER JOIN currency c ON c.currency_id = `al`.`currency_id`
                                 WHERE al.artist_id =:artist_id
                                    and al.quarter =:quarter
                                           AND al.currency_id = :currency_id
                                    and YEAR(`date_added`) =:year")
            ->bindValue(':artist_id', $model->id)
            ->bindValue(':quarter', 3)
            ->bindValue(':currency_id', 1)
            ->bindValue(':year', date('Y'))
            ->queryAll();

        $all_uah = Yii::$app->db->createCommand(
            "SELECT alt.name, `al`.`sum`, c.currency_name
                                    FROM `artist_log` `al` 
                                    INNER JOIN artist_log_type alt ON alt.log_type_id = `al`.`type_id` 
                                    INNER JOIN currency c ON c.currency_id = `al`.`currency_id`
                                 WHERE al.artist_id =:artist_id
                                    and al.quarter =:quarter
                                           AND al.currency_id = :currency_id
                                    and YEAR(`date_added`) =:year")
            ->bindValue(':artist_id', $model->id)
            ->bindValue(':quarter', 3)
            ->bindValue(':currency_id', 2)
            ->bindValue(':year', date('Y'))
            ->queryAll();

        $costs_euro = Yii::$app->db->createCommand(
            "SELECT it.invoice_type_name, ii.date_item, a.name as a_name, t.name as t_name, ii.description, ii.amount, c.currency_name 
                    FROM `invoice_items` ii 
                        LEFT JOIN artist a ON a.id = ii.artist_id 
                        LEFT JOIN track t ON t.id = ii.track_id 
                        LEFT JOIN invoice i ON i.invoice_id = ii.invoice_id 
                        LEFT JOIN currency c ON c.currency_id = i.currency_id 
                        left join invoice_type it ON it.invoice_type_id = i.invoice_type 
                    WHERE i.invoice_status_id in (2, 4) 
                      and i.invoice_type in (3, 4) 
                      and i.currency_id =:currency_id
                        and i.date_added >= '2024-07-01'#a.date_last_payment 
                      and ii.artist_id =:artist_id
    ")
            ->bindValue(':artist_id', $model->id)
            ->bindValue(':currency_id', 1)
            ->queryAll();

        $costs_uah = Yii::$app->db->createCommand(
            "SELECT it.invoice_type_name, ii.date_item, a.name as a_name, t.name as t_name, ii.description, ii.amount, c.currency_name 
                    FROM `invoice_items` ii 
                        LEFT JOIN artist a ON a.id = ii.artist_id 
                        LEFT JOIN track t ON t.id = ii.track_id 
                        LEFT JOIN invoice i ON i.invoice_id = ii.invoice_id 
                        LEFT JOIN currency c ON c.currency_id = i.currency_id 
                        left join invoice_type it ON it.invoice_type_id = i.invoice_type 
                    WHERE i.invoice_status_id in (2, 4) 
                        and i.invoice_type in (3, 4) 
                        and i.currency_id =:currency_id
                        and i.date_added >= '2024-07-01'#a.date_last_payment 
                        and ii.artist_id =:artist_id
    ")
            ->bindValue(':artist_id', $model->id)
            ->bindValue(':currency_id', 2)
            ->queryAll();

        $content = $this->render(
            'balance',
            [
                'all_euro' => $all_euro,
                'all_uah' => $all_uah,
                'costs_euro' => $costs_euro,
                'costs_uah' => $costs_uah,
            ]
        );

        $reader = new Html();
        $writer = new Xlsx($reader->loadFromString($content));
        $writer->save($filename);

        $this->redirect("/balance_q3_{$d}_{$name}.xlsx");
    }

    /**
     * Звіт артиста по останній виплаті
     *
     * @param int $id
     * @return string
     * @throws NotFoundHttpException
     * @throws \yii\db\Exception
     */
    public function actionExportAct(int $id, int $quarter = null, int $year = null, bool $redirect = true)
    {
        $model = $this->findModel($id);
        //$lastInvoice = $model->getLastPayInvoice();
        
        if (is_null($quarter)|| is_null($year)) {
            $lastInvoice = (new \yii\db\Query())
                ->from(InvoiceItems::tableName())
                ->select('invoice.invoice_id,
             invoice.currency_id,
              invoice.quarter,
               invoice.year,
                invoice.date_pay,
                 invoice.date_added,
                  abs(invoice_items.amount) as amount
            ')
                ->innerJoin(Invoice::tableName(), 'invoice.invoice_id = invoice_items.invoice_id')
                ->where([
                    'invoice.invoice_status_id' => [2, 4],
                    'invoice.invoice_type' => 2,
                ])->orderBy(['invoice.year' => SORT_DESC, 'invoice.quarter' => SORT_DESC])
                ->limit(1)
                ->one();
            
            $quarter = $lastInvoice['quarter'] ?? null;
            $year = $lastInvoice['year'] ?? null;
        }
        
        if (null == $quarter || null == $year) {
            Yii::$app->session->setFlash('error', 'Генерація звіту доступна лише в період виплати!');
            
            return '';
        }
        
        $name = Str::transliterate($model->name);
        $filename = "report_q_{$quarter}_{$year}_{$name}.xlsx";
        
        if (false /*file_exists(self::$homePage . 'xls/' . $filename)*/) {
            if ($redirect) {
                header("Location: /xls/".$filename);
                exit;
            } else {
                return $filename;
            }
        }
        
        $all_euro = Artist::getLog(
            $model->id,
            $quarter,
            $year,
            1,
            'EUR'
        );
        
        $all_usd = Artist::getLog(
            $model->id,
            $quarter,
            $year,
            3,
            'USD'
        );
        
        $all_uah = Artist::getLog(
            $model->id,
            $quarter,
            $year,
            2,
            'UAH'
        );
        
        $spreadSheet = new Spreadsheet();
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
        $workSheet->getStyle('A1:G1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('BFBFBF');;
        $workSheet->getStyle("A1:G1")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('000000'); // чорний
        
        $workSheet->getStyle('A3:G3')->getFont()->setBold(true);
        $workSheet->getStyle('A3:G3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('BFBFBF');
        $workSheet->getStyle("A3:G3")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('000000'); // чорний
        //$workSheet->getStyle("A3:G3")->getBorders()->getInside()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('000000'); // чорний
        //$workSheet->getStyle("A14:G14")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('000000'); // чорний
        
        $workSheet->getStyle("A3:G14")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('000000'); // чорний
        $workSheet->getStyle('A14:G14')->getFont()->setBold(true);
        $tempData = [];
        $tempData[0] = ['Звіт за ' . $quarter . ' кв. ' . $year . ', ' . $model->name];  // 1
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
        
        foreach ($all_euro as $key => $item) {
            $tempData[$i] = [
                0 => strip_tags($item['name']),
                1 => number_format($item['value'], 2, '.', ''),
                2 => $item['currency_name'],
                3 => number_format($all_usd[$key]['value'] ?? 0, 2, '.', ''),
                4 => $all_usd[$key]['currency_name'] ?? '',
                5 => number_format($all_uah[$key]['value'] ?? 0, 2, '.', ''),
                6 => $all_uah[$key]['currency_name'] ?? '',
            ];
            $i++; // 15
        }
        
        // доп. дохід за період
        $income = $model->getIncome($quarter, $year);
        // витрати за період
        $costs = $model->getCosts($quarter, $year);
        // витрати настпуний період
        $nextQuarter = DateFormat::getNextQuarterYear($quarter, $year);
        $costs_next = $model->getCosts($nextQuarter['quarter'], $nextQuarter['year']);
        
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
            $workSheet->getStyle("A$i:F$i")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('BFBFBF');
            
            //$workSheet->getStyle("A$i:G$i")->getBorders()->getInside()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('000000'); // чорний
            //$workSheet->getStyle("A$i:G$i")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('000000'); // чорний
            $workSheet->getStyle("A$i:F" . ($i + count($income) + count($costs) + count($costs_next)))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('000000'); // чорний
            
            $i++; // 18
            foreach ($income as $item) {
                $tempData[$i] = [
                    0 => $item['description'],
                    1 => $item['invoice_type_name'] == 'Баланс' ? 'Нарахування' : $item['invoice_type_name'],
                    2 => number_format($item['amount'], 2, '.', ''),
                    3 => $item['currency_name'],
                    4 => $quarter . ' кв.',
                    5 => $item['date_item'],
                ];
                $i++;
            }
            
            foreach ($costs as $item) {
                $tempData[$i] = [
                    0 => $item['description'],
                    1 => $item['invoice_type_name'],
                    2 => number_format($item['amount'], 2, '.', ''),
                    3 => $item['currency_name'],
                    4 => $quarter . ' кв.',
                    5 => $item['date_item'],
                ];
                $i++;
            }

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
        
        $workSheet->fromArray($tempData, null, 'A1');
        $workSheet->setSelectedCell('A1');
        $data = Yii::$app->db->createCommand(
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
            HAVING amount_2 > 0
            ")
            ->bindValue(':artist_id', $model->id)
            ->bindValue(':quarter', $quarter)
            ->bindValue(':year', $year)
            ->queryAll();
        
        if (count($data)) {
            $spreadSheet->createSheet();
            $spreadSheet->setActiveSheetIndex(1);
            $workSheet = $spreadSheet->getActiveSheet();
            $workSheet->setTitle('Звіт');
            
            $workSheet->getStyle('A1:N1')->getAlignment()->setWrapText(true)
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::HORIZONTAL_CENTER);
                
                $workSheet->getStyle("A1:N1")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('BFBFBF');
                
               /// $workSheet->getStyle("A1:N1")->getBorders()->getInside()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('000000'); // чорний
                //$workSheet->getStyle("A1:N1")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('000000'); // чорний
                
                $workSheet->getStyle("A1:N" . (count($data) + 1))
                    ->getBorders()
                    ->getAllBorders()
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
            $workSheet->setSelectedCell('A1');
            // $reader = new Html();
            // $data = $reader->loadFromString($content, $spreadSheet);
            $spreadSheet->setActiveSheetIndex(0);
        }
        
        $writer = new Xlsx($spreadSheet);
        $writer->save(self::$homePage . 'xls/' . $filename);
        
        if ($redirect) {
            header("Location: /xls/".$filename);
            exit;
          //  $this->redirect("/xls/" . $filename);
        }

      return $filename;
    }
    
    public function actionExportArtist()
    {
        $sql = "SELECT `id`, `name`, `full_name`, `email` FROM `artist` a WHERE a.country_id = 1 ORDER BY `a`.`id` ASC";
        
        $data = Yii::$app->db->createCommand($sql)
            ->queryAll();
        
        if (empty($data)) {
            Yii::$app->session->setFlash('error', 'Дані не знайдені');
            $this->redirect(['artist/index']);
        }
        
        $tempData[] = [
            'Артист ID',
            'Нікнейм',
            'ПІБ',
            'email',
        ];
        
        $tempData = array_merge($tempData, $data);
        
        $spreadSheet = new Spreadsheet();
        // баланси
        $workSheet = $spreadSheet->getActiveSheet();
        $workSheet->setTitle('Список артистів');
        $workSheet->getStyle('A1:J1')->getAlignment()
            ->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workSheet->getStyle('A1:J1')->getFont()->setBold(true);
        // зберегти баланс на першому аркуші
        $workSheet->fromArray($tempData);
        $filename = "all_artist_list.xlsx";
        $writer = new Xlsx($spreadSheet);
        $writer->save(self::$homePage . 'xls/' . $filename);
        
        $this->redirect("/xls/".$filename);
    }
    
    public function actionMail($id)
    {
        $model = $this->findModel($id);
        
       // $model->email = 'yaroslav_148@icloud.com'; // test email
        //$model->email = 'Komar@blackbeatsmusic.com'; // test email
        
        $lastInvoice = (new \yii\db\Query())
            ->from(InvoiceItems::tableName())
            ->select('invoice.invoice_id,
             invoice.currency_id,
              invoice.quarter,
               invoice.year,
                invoice.date_pay,
                 invoice.date_added,
                  abs(invoice_items.amount) as amount
            ')
            ->innerJoin(Invoice::tableName(), 'invoice.invoice_id = invoice_items.invoice_id')
            ->where([
                'invoice.invoice_status_id' => [2, 4],
                'invoice.invoice_type' => 2,
            ])->orderBy(['invoice.year' => SORT_DESC, 'invoice.quarter' =>  SORT_DESC])
            ->limit(1)
            ->one();
        
        $reportFileName = $this->actionExportAct($id, $lastInvoice['quarter'], $lastInvoice['year'], false);
        
        if (empty($reportFileName)) {
            throw new \RuntimeException("Артисту {$model->name} не вдалось відправити звіт! Відсутній файл звіту. Зверніться до адміністратора.");
        }
        
        $excel = self::$homePage .  'xls/' . $reportFileName;
        $attach[] = [$excel, ['fileName' => $reportFileName]];
        
        $mail = new Mail([
            'from' => ['reports@blackbeatsmusic.com' => 'Black Beats Reports'],
            'to' => [$model->email => $model->name],
            'subject' => "Black Beats | Royalty Report Q{$lastInvoice['quarter']} {$lastInvoice['year']}",
            //'bcc' => 'reports@blackbeatsmusic.com',
            'bcc' => 'gmmkam123@gmail.com',
            'replyTo' => 'reports@blackbeatsmusic.com',
            'view' => [
                'html' => 'artistBalanceNotification-html',
            ],
            'params' => [
                'artist' => $model,
                'quarter' => $lastInvoice['quarter'],
                'year' => $lastInvoice['year'],
            ],
            'attach' => $attach,
        ]);
        
        if ($mail->send('Balance Notification', $model)) {
           // Yii::$app->session->setFlash('success', "Артисту {$model->name} успішно відправлено звіт!");
            return 'Артисту ' . $model->name . ' успішно відправлено звіт на email:' . $model->email;
        }
        
        throw new \RuntimeException("Артисту {$model->name} не вдалось відправити звіт! Зверніться до адміністратора.");
        //  Yii::$app->session->setFlash('error', "Артисту {$model->artist->name} не вдалось відправлено звіт! Зверніться до адміністратора.");
        
       // return 'Артисту ' . $model->name . ' успішно відправлено звіт!';
        
       /* $logs = $model->getInvoiceLogs('Balance Notification');
        
        $logs = array_filter($logs, function($log) {
            return date('m', strtotime($log->date_added)) == date('m');
        });
        
        $titleLog = "Відправлено на email: {$model->email} в такі дати:\n";
        
        foreach ($logs as $log) {
            $titleLog .= date('d.m.Y H:i:s', strtotime($log->date_added)) . "\n";
        }
        
        return '<span class="glyphicon glyphicon-ok text-success" data-toggle="tooltip" data-placement="top" data-title=" ' . $titleLog. '"></span>';
        */
        
        // return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
    }

    /**
     * Finds the Artist model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Artist the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Artist::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
