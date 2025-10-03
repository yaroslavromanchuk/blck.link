<?php

namespace backend\controllers;

use backend\models\ArtistLog;
use backend\models\Invoice;
use backend\models\InvoiceItems;
use backend\widgets\DateFormat;
use backend\widgets\Str;
use PhpOffice\PhpSpreadsheet\Reader\Html;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
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
       // $i = 0;
        //$j = 0;
        //foreach ($dataProvider->models as $model) {
           // if(!$model->isSavedBalance(4, 2, 2024)) {
             //   $model->saveBalance(4, 2, 2024);
           // }
            /*if($model->isSavedBalance(4, 1, 2024)) {
                $i++;
               $model->saveBalance(4, 1, 2024);
            }*/
      //  }
//echo $j.PHP_EOL;
      //  echo $i;

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
        return $this->render('view', [
            'model' => $this->findModel($id),
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

        if($model->load(Yii::$app->request->post())) {
            
            $file = UploadedFile::getInstance($model, 'file');

            if ($file && $file->tempName) {
               
                $model->file = $file;

                if ($model->validate(['file'])) {
                   $model->logo = Upload::updateImage($model, $model->logo, 'artist', [60, 60]);
                }
            }

             if($model->validate() && $model->save()) {
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

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
           $model->load(Yii::$app->request->post());

            return ActiveForm::validate($model);
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {

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

                return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);
            } else {
                Yii::$app->session->setFlash('error', 'Не вдалось створити інвойс');

                return $this->redirect(['artist/index']);
            }
        } else {
            $errors = $model->getErrors();

            Yii::$app->session->setFlash('error', 'Помилка сворення інвойсу на виплату: ' .current($errors));
        }

        return $this->redirect(['artist/index']);
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
     * @return void
     * @throws NotFoundHttpException
     * @throws \yii\db\Exception
     */
    public function actionExportAct(int $id, int $quarter = null, int $year = null)
    {
        $model = $this->findModel($id);
        //$lastInvoice = $model->getLastPayInvoice();
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
                'invoice.invoice_status_id' => 2,
                'invoice.invoice_type' => 2,
            ])->orderBy(['invoice.year' => SORT_DESC, 'invoice.quarter' =>  SORT_DESC])
            ->limit(1)
            ->one();
        $quarter = $lastInvoice['quarter'] ?? DateFormat::getQuarterNumber();
        $year = $lastInvoice['year'] ?? date('Y');

        $name = Str::transliterate($model->name);
        $filename = "report_q_{$quarter}_{$year}_{$name}.xlsx";

        if (file_exists(self::$homePage . 'xls/' .  $filename)) {
            $this->redirect("/xls/" . $filename);
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
        $workSheet->getColumnDimension('B')->setWidth(10);
        $workSheet->getColumnDimension('C')->setWidth(10);
        $workSheet->mergeCells('A1:C1');

        $workSheet->getStyle('A1:K1')->getFont()->setBold(true);
        $workSheet->getStyle('A2:Q2')->getFont()->setBold(true);

        $tempData = [];
        $tempData[] = ['Звіт за ' . $quarter . ' кв. ' . $year . ', ' . $model->name];

        $tempData[] = [
            'Операція',
            'Сума',
            'Валюта'
        ];

        $temp = array_map(function ($item) {
            return [
                'name' => strip_tags($item['name']),
                'sum' => number_format($item['value'],2, '.', ''),
                'currency' => $item['currency_name']
            ];
        }, $all_euro);

        $tempData = array_merge($tempData, $temp);

        $co = count($tempData);
        $co+=2;
        $tempData[] = [];

        $workSheet->getStyle("A{$co}:C{$co}")->getFont()->setBold(true);

        $tempData[] = [
            'Операція',
            'Сума',
            'Валюта'
        ];

        $temp = array_map(function ($item) {
            return [
                'name' => strip_tags($item['name']),
                'sum' => number_format($item['value'],2, '.', ''),
                'currency' => $item['currency_name']
            ];
        }, $all_usd);

        $tempData = array_merge($tempData, $temp);
        $co = count($tempData);
        $co+=2;
        $tempData[] = [];

        $workSheet->getStyle("A{$co}:C{$co}")->getFont()->setBold(true);

        $tempData[] = [
            'Операція',
            'Сума',
            'Валюта'
        ];

        $temp = array_map(function ($item) {
            return [
                'name' => strip_tags($item['name']),
                'sum' => number_format($item['value'],2, '.', ''),
                'currency' => $item['currency_name']
            ];
        }, $all_uah);

        $tempData = array_merge($tempData, $temp);

        $workSheet->fromArray($tempData, null, 'A1');
        $tempData = [];

        $income = Yii::$app->db->createCommand(
            "SELECT it.invoice_type_name, ii.date_item, a.name as a_name, t.name as t_name, ii.description, ii.amount, c.currency_name 
                    FROM `invoice_items` ii 
                        LEFT JOIN artist a ON a.id = ii.artist_id 
                        LEFT JOIN track t ON t.id = ii.track_id 
                        LEFT JOIN invoice i ON i.invoice_id = ii.invoice_id 
                        LEFT JOIN currency c ON c.currency_id = i.currency_id 
                        left join invoice_type it ON it.invoice_type_id = i.invoice_type 
                    WHERE i.invoice_status_id in (2, 4)  
                      and i.invoice_type = 5 
                      and i.quarter =:quarter
                      and i.year =:year
                      and ii.artist_id =:artist_id
                    ORDER BY ii.date_item, i.currency_id
            ")
            ->bindValue(':quarter', $quarter)
            ->bindValue(':year', $year)
            ->bindValue(':artist_id', $model->id)
            ->queryAll();

        if (count($income)) {
            $tempData[] = ['Додавткові надходження'];
            $workSheet->mergeCells('E1:I1');
            $tempData[] = ['Дата', 'Виконавець', 'Стаття витрат', 'Сума', 'Валюта'];
            $temp = array_map(function ($item) {
                return [
                    'date_item' => $item['date_item'],
                    'a_name' => $item['a_name'],
                    'description' => $item['description'],
                    'amount' => number_format($item['amount'],2, '.', ''),
                    'currency_name' => $item['currency_name']
                ];
            }, $income);

            $tempData = array_merge($tempData, $temp);

            $workSheet->getColumnDimension('E')->setWidth(12);
            $workSheet->getColumnDimension('F')->setWidth(12);
            $workSheet->getColumnDimension('G')->setWidth(30);
            $workSheet->getColumnDimension('H')->setWidth(12);
            $workSheet->getColumnDimension('I')->setWidth(12);

            $workSheet->fromArray($tempData, null, 'E1');
            $tempData = [];
        }

        $costs = Yii::$app->db->createCommand(
            "SELECT it.invoice_type_name, ii.date_item, a.name as a_name, t.name as t_name, ii.description, ii.amount, c.currency_name 
                    FROM `invoice_items` ii 
                        LEFT JOIN artist a ON a.id = ii.artist_id 
                        LEFT JOIN track t ON t.id = ii.track_id 
                        LEFT JOIN invoice i ON i.invoice_id = ii.invoice_id 
                        LEFT JOIN currency c ON c.currency_id = i.currency_id 
                        left join invoice_type it ON it.invoice_type_id = i.invoice_type 
                    WHERE i.invoice_status_id in (2, 4)  
                      and i.invoice_type in (3, 4)
                      and i.quarter =:quarter
                      and i.year =:year
                      and ii.artist_id =:artist_id
                    ORDER BY ii.date_item, i.currency_id
            ")
            ->bindValue(':quarter', $quarter)
            ->bindValue(':year', $year)
            ->bindValue(':artist_id', $model->id)
            ->queryAll();

        if (count($costs)) {
            $tempData[] = ['Витрати'];
            $tempData[] = ['Дата', 'Тип', 'Виконавець', 'Трек', 'Стаття витрат', 'Сума', 'Валюта'];
            $temp = array_map(function ($item) {
                return [
                    'date_item' => $item['date_item'],
                    'invoice_type_name' => $item['invoice_type_name'],
                    'a_name' => $item['a_name'],
                    't_name' => $item['t_name'],
                    'description' => $item['description'],
                    'amount' => number_format($item['amount'],2, '.', ''),
                    'currency_name' => $item['currency_name']
                ];
            }, $costs);

            $tempData = array_merge($tempData, $temp);

            if (count($income)) {
                $workSheet->mergeCells('K1:Q1');
                $workSheet->getColumnDimension('K')->setWidth(12);
                $workSheet->getColumnDimension('L')->setWidth(12);
                $workSheet->getColumnDimension('M')->setWidth(12);
                $workSheet->getColumnDimension('N')->setWidth(12);
                $workSheet->getColumnDimension('O')->setWidth(30);
                $workSheet->getColumnDimension('P')->setWidth(8);
                $workSheet->getColumnDimension('Q')->setWidth(8);
                $workSheet->fromArray($tempData, null, 'K1');
            } else {
                $workSheet->mergeCells('E1:I1');
                $workSheet->getColumnDimension('E')->setWidth(12);
                $workSheet->getColumnDimension('F')->setWidth(12);
                $workSheet->getColumnDimension('G')->setWidth(30);
                $workSheet->getColumnDimension('H')->setWidth(8);
                $workSheet->getColumnDimension('I')->setWidth(8);
                $workSheet->fromArray($tempData, null, 'E1');
            }
        }

        $data = Yii::$app->db->createCommand(
            "SELECT  a.name as artist_name,
                    t.name as track_name,
                    ari.count, 
                    t2p.percentage,
                    t2p2.percentage as percentage_label,
                    o.name as prav1,
                    IFNULL(atu.name, a2ow.name) as prav2,
                    IFNULL(a_s.name, ari.platform) as platform,
                    ari.date_report,
                    ari.country,
                    c.currency_name,
                    ari.count,
                    IF(t2p.percentage != 100, t2p.percentage / 100 * ari.amount, ari.amount) as amount
                FROM `invoice_items` ii
                	INNER JOIN invoice i ON i.invoice_id = ii.invoice_id and i.invoice_type = 1
                    INNER JOIN track t ON REPLACE(t.isrc, '-', '') = REPLACE(ii.isrc, '-', '') and ii.artist_id = t.artist_id
                    LEFT JOIN currency c ON c.currency_id = i.currency_id
                    LEFT JOIN artist a ON a.id = ii.artist_id
                    
                    LEFT JOIN aggregator_report ar ON ar.id = i.aggregator_report_id
                    LEFT JOIN `aggregator_report_item` ari ON ari.report_id = ar.id and ii.isrc = ari.isrc
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
                        and t2p.artist_id = ii.artist_id 
                        and t2p.track_id = t.id
                   LEFT JOIN track_to_percentage t2p2 ON t2p2.track_id = t.id and t2p2.artist_id = a.id and t2p2.ownership_type = 5 
                WHERE i.quarter =:quarter
                  AND i.year = :year
                  AND t.artist_id =:artist_id")
            ->bindValue(':quarter', $quarter)
            ->bindValue(':year', $year)
            ->bindValue(':artist_id', $model->id)
            ->queryAll();

        $feats = [];

        if ($model->label_id == 0) {
            $feats = Yii::$app->db->createCommand(
                "SELECT  a.name as artist_name,
                    t.name as track_name,
                    ari.count, 
                    t2p.percentage,
                    t2p2.percentage as percentage_label,
                    o.name as prav1,
                    IFNULL(atu.name, a2ow.name) as prav2,
                    IFNULL(a_s.name, ari.platform) as platform,
                    ari.date_report,
                    ari.country,
                    c.currency_name,
                    ari.count,
                    IF(t2p.percentage != 100, t2p.percentage / 100 * ari.amount, ari.amount) as amount
                FROM `invoice_items` ii
                	INNER JOIN invoice i ON i.invoice_id = ii.invoice_id and i.invoice_type = 1
                    INNER JOIN track t ON REPLACE(t.isrc, '-', '') = REPLACE(ii.isrc, '-', '') 
                        and ii.artist_id != t.artist_id
                    LEFT JOIN currency c ON c.currency_id = i.currency_id
                    LEFT JOIN artist a ON a.id = ii.artist_id
                    
                    LEFT JOIN aggregator_report ar ON ar.id = i.aggregator_report_id
                    LEFT JOIN `aggregator_report_item` ari ON ari.report_id = ar.id and ii.isrc = ari.isrc
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
                        and t2p.artist_id = ii.artist_id 
                        and t2p.track_id = t.id
                   LEFT JOIN track_to_percentage t2p2 ON t2p2.track_id = t.id and t2p2.artist_id = a.id and t2p2.ownership_type = 5 
                WHERE  i.quarter =:quarter
                    and i.year = :year
                    AND ii.artist_id =:artist_id")
                ->bindValue(':quarter', $quarter)
                ->bindValue(':year', $year)
                ->bindValue(':artist_id', $model->id)
                ->queryAll();
        }

        $spreadSheet->createSheet();
        $spreadSheet->setActiveSheetIndex(1);
        $workSheet = $spreadSheet->getActiveSheet();
        $workSheet->setTitle('Звіт');

        $workSheet->getStyle('A1:N1')->getAlignment()->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::HORIZONTAL_CENTER);
        /*
        $workSheet->getStyle('B1')->getAlignment()->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::HORIZONTAL_CENTER);
        $workSheet->getStyle('C1')->getAlignment()->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::HORIZONTAL_CENTER);
        $workSheet->getStyle('D1')->getAlignment()->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::HORIZONTAL_CENTER);
        $workSheet->getStyle('E1')->getAlignment()->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::HORIZONTAL_CENTER);
        $workSheet->getStyle('F1')->getAlignment()->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::HORIZONTAL_CENTER);
        $workSheet->getStyle('G1')->getAlignment()->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::HORIZONTAL_CENTER);
        $workSheet->getStyle('H1')->getAlignment()->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::HORIZONTAL_CENTER);
        $workSheet->getStyle('I1')->getAlignment()->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::HORIZONTAL_CENTER);
        $workSheet->getStyle('J1')->getAlignment()->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::HORIZONTAL_CENTER);
        $workSheet->getStyle('K1')->getAlignment()->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::HORIZONTAL_CENTER);
        $workSheet->getStyle('L1')->getAlignment()->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::HORIZONTAL_CENTER);
        $workSheet->getStyle('M1')->getAlignment()->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::HORIZONTAL_CENTER);
        $workSheet->getStyle('N1')->getAlignment()->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::HORIZONTAL_CENTER);*/

        $workSheet->getColumnDimension('A')->setWidth(4);
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
            // Skip items with empty amount
            if (empty($item['amount'])) {
                continue;
            }

            $amount = round($item['amount'] * ($item['percentage_label'] /100), 4);
            $tempData[] = [
                $i,
                $item['artist_name'],
                str_replace("1", "", $item['track_name']),
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
                DateFormat::datumUah2($item['date_report'] ?? 'now'),
            ];
            $i++;
        }

        foreach ($feats as $item) {
            // Skip items with empty amount
            if (empty($item['amount'])) {
                continue;
            }

            $amount = round($item['amount'] * ($item['percentage_label'] /100), 4);
            $tempData[] = [
                $i,
                $item['artist_name'],
                str_replace("1", "", $item['track_name']),
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
                DateFormat::datumUah2($item['date_report'] ?? 'now'),
            ];
            $i++;
        }

        $workSheet->fromArray($tempData);
       // $reader = new Html();
       // $data = $reader->loadFromString($content, $spreadSheet);
        $spreadSheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadSheet);
        $writer->save(self::$homePage . 'xls/' . $filename);

        $this->redirect("/xls/" . $filename);
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
