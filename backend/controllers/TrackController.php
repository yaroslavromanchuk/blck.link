<?php

namespace backend\controllers;

use backend\helpers\InvoiceAllocationService;
use backend\helpers\Isrc;
use backend\models\AggregatorReportItem;
use backend\models\Invoice;
use backend\models\InvoiceItems;
use backend\models\InvoiceStatus;
use backend\models\InvoiceType;
use backend\models\Perc;
use backend\models\Percentage;
use backend\models\PercentageSearch;
use backend\models\ReleaseSearch;
use backend\models\SubLabel;
use backend\models\UploadReport;
use backend\models\UserBalance;
use common\models\t;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Throwable;
use Yii;
use backend\models\Track;
use backend\models\Artist;
use backend\models\TrackSearch;
use yii\base\Exception;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use backend\models\Upload;
use yii\web\UploadedFile;
use yii\web\Response;
use yii\bootstrap\ActiveForm;
use yii\base\Model;

use yii\filters\AccessControl;


/**
 * TrackController implements the CRUD actions for Track model.
 */
class TrackController extends Controller
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
                    'recalculate-invoices' => ['POST'],
                   // 'percentage-update' => ['POST', 'GET'],
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
                        'actions' => ['index', ],
                        'roles' => ['@'], // '?' – гість, '@' – автентифікований
                    ],
                    // Створення/оновлення тільки для залогінених
                    [
                        'allow' => true,
                        'actions' => ['view', 'create', 'update', 'copy', 'import', 'analytics', 'percentage', 'percentage-create', 'percentage-update', 'load-modal', 'percentage-delete', 'export-track', 'recalculate-invoices'],
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
     * Lists all Track models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new TrackSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        //$this->getT();
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    private function getT()
    {
        $traks = Track::find()->all();

        foreach ($traks as $track) {

            $pc = $track->getPercentage();

           // print_r($pc); exit();

           if (!empty($pc) && $pc[4]['type_name'] == 'Загальний відсоток' && $pc[4]['percentage'] == 0) {
               echo $track->id;
                $track->getPR();
                echo ' +'.PHP_EOL;
            }
        }
        exit;
    }

    /**
     * Displays a single Track model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView(int $id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Track model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Track();

		if(Yii::$app->request->isAjax && Yii::$app->request->post('ajax')) {
        	if ($model->load(Yii::$app->request->post())){
                if (is_array($model->servise)) {
                    $model->servise = serialize($model->servise);
                }
                
                if (empty($model->url)) {
                    $model->url = trim(Yii::$app->translit->t($model->name));
                    if ($model->validate(['url'])) {
                        $model->url = '';
                    }
                }
                
            	Yii::$app->response->format = Response::FORMAT_JSON;

        	    return ActiveForm::validate($model);
       		}
        	return true;
      }

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $success = false;
            $redirect = '';
            $errors = [];
            $oldModel = $model->toArray();
            
            if ($model->load(Yii::$app->request->post())) {
                $file = UploadedFile::getInstance($model, 'file');
                
                if ($file && $file->tempName) {
                    $model->file = $file;
                    $id = Track::find()
                        ->orderBy('id DESC')
                        ->one()
                        ->id;
                    $id++;
                    
                    if ($model->validate(['file'])) {
                        $model->img = Upload::createImage($model, $id, 'track', [500, 500]);
                    }
                } else {
                    $model->img = '2565_XZEVWO7R.jpg';
                }
                
                $model->name = trim($model->name);
                
                if (empty($model->url)) {
                    $model->url = trim(Yii::$app->translit->t($model->name));//     Yii::$app->getSecurity()->generateRandomString(8);
                }
                

                $isrcObject = new Isrc(str_replace("-", "", trim($model->isrc)));
                $model->isrc = $isrcObject->getIsrc(false);
                $model->servise = serialize($model->servise);
                
                if ($model->validate() && $model->save()) {
                    $isArtist = $model->artist->isArtist();
                    
                    if (!$model->is_album && $isArtist) {
                        $model->addArtistPercentage();
                    }
                    
                    $feeds = Yii::$app->request->post('Track')['feeds'] ?? [];
                    
                    if (!empty($feeds) && is_array($feeds) && $isArtist) {
                        $model->saveFeeds(Yii::$app->request->post('Track')['feeds'] ?? []);
                    }
                    
                    if (Yii::$app->user->id != 16) {
                        $message = $model->is_album == 1 ? 'трек: ' . $model->name : 'трек: ' . $model->name . ' (' . $model->isrc . ')';
                        t::log(Yii::$app->user->identity->getFullName() . "\nДодав " . $message, 529871503);
                    }
                    
                    $success = true;
                    
                    $model->saveLog($oldModel, $model->toArray());
                    
                    $redirect = Url::to(['view', 'id' => $model->id]);
                } else {
                    $errors = $model->getErrors();
                }
            } else {
                $errors = $model->getErrors();
            }
            
            return [
                'success' => $success,
                'redirect' => $redirect,
                'errors' => $errors,
            ];
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * @throws NotFoundHttpException
     */
    public function actionAnalytics(int $id): string
    {
        $model = $this->findModel($id);
        
        return $this->render('analytics', [
            'model' => $model,
            'link' => $model->getLogsLink(),
            'servise' => $model->getLogsServise()
        ]);
        
    }
    public function actionCopy(int $id)
    {
        if(Yii::$app->request->isAjax) {
            $model = new Track();
            if ($model->load(Yii::$app->request->post())){
                Yii::$app->response->format = Response::FORMAT_JSON;

                return ActiveForm::validate($model);
            }

            return true;
        }

        if (Yii::$app->request->isPost) {
            $model = new Track();
            if ($model->load(Yii::$app->request->post())) {
                $model->name = trim($model->name);
                if (empty($model->url)) {
                    $model->url = trim(Yii::$app->translit->t($model->name));//     Yii::$app->getSecurity()->generateRandomString(8);
                }

                $model->isrc = trim($model->isrc);
                $model->servise = serialize($model->servise);

                if($model->validate() && $model->save()) {

                    if (!$model->is_album && !$model->isSubLabel()) {
                        $model->addArtistPercentage();
                    }

                    $feeds = Yii::$app->request->post('Track')['feeds']?? [];

                    if (!empty($feeds) && is_array($feeds)) {
                        $model->saveFeeds(Yii::$app->request->post('Track')['feeds']?? []);
                    }

                    if (Yii::$app->user->id != 16) {
                        $message = $model->is_album == 1 ? 'трек: ' . $model->name : 'трек: ' . $model->name . ' (' . $model->isrc . ')';
                        t::log(Yii::$app->user->identity->getFullName()  . "\nДодав ". $message, 529871503);
                    }

                    return $this->redirect(['view', 'id' => $model->id]);
                } else {
                    return $this->render('copy', [
                        'model' => $model,
                    ]);
                }
            }
        }

        $model = $this->findModel($id);

        $model->id = null;
        $model->isrc = null;
        $model->name = $model->name . ' - Копія';
        $model->sharing = 0;
        $model->url = $model->url . '/copy';

        return $this->render('copy', [
            'model' => $model,
        ]);
    }

	/**
	 * Updates an existing Track model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 * @throws Exception
	 */
    public function actionUpdate(int $id)
    {
        $model = $this->findModel($id);
        $model->feeds = $model->getFeeds();
        
        $oldImg = $model->img;
        $oldAdmin = $model->admin_id;
        
        if(Yii::$app->request->isAjax && Yii::$app->request->post('ajax')) {
            if ($model->load(Yii::$app->request->post())){
                if (is_array($model->servise)) {
                    $model->servise = serialize($model->servise);
                } else {
                    $model->servise = serialize([]);
                }
                
                Yii::$app->response->format = Response::FORMAT_JSON;
                
                return ActiveForm::validate($model);
            }
            return true;
        }
        
        if (Yii::$app->request->isAjax) {
            $oldModel = $model->toArray();
            $success = false;
            $redirect = '';
            $errors = [];
            Yii::$app->response->format = Response::FORMAT_JSON;
            
            if ($model->load(Yii::$app->request->post())) {
                $file = UploadedFile::getInstance($model, 'file');
                
                if ($file) {
                    $model->file = $file;
                    if ($model->validate('file')) {
                        $model->img = Upload::updateImage($model, $model->img, 'track', [500, 500]);
                    }
                } else {
                    $model->img = $oldImg;
                }
                
                if (is_array($model->servise)) {
                    $model->servise = serialize($model->servise);
                } else {
                    $model->servise = serialize([]);
                }

                $isrcObject = new Isrc(str_replace("-", "", trim($model->isrc)));
                $model->isrc = $isrcObject->getIsrc(false);
                
                $model->admin_id = $oldAdmin;
                
                if ($model->validate() && $model->save()) {
                    
                    if (!$model->is_album && count(Percentage::findAll(['track_id' => $model->id, 'artist_id' => $model->artist_id])) != 4) {
                        $model->updateArtistPercentage();
                    }
                    
                    $feeds = Yii::$app->request->post('Track')['feeds'] ?? [];
                    
                    if (is_string($feeds)) {
                        $feeds = [];
                    }
                    
                    $client = $model->artist->isClient();
                    
                    if (!$client) {
                        $model->saveFeeds($feeds);
                    }
                   
                    $model->saveLog($oldModel, $model->toArray());
                    
                    $success = true;
                    $redirect = Url::to(['view', 'id' => $model->id]);
                } else {
                    $errors = $model->getErrors();
                }
            } else {
                $errors = $model->getErrors();
            }
            
            return [
                'success' => $success,
                'redirect' => $redirect,
                'errors' => $errors,
            ];
        }
        
        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Track model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return Response
     * @throws NotFoundHttpException if the model cannot be found
     * @throws StaleObjectException
     * @throws \Throwable
     */
    public function actionDelete(int $id): Response
    {
        $this->findModel($id)->delete();

        return $this->redirect(Yii::$app->request->referrer ?: Yii::$app->homeUrl);
    }
    
    public function actionExportTrack()
    {
        $sql = "SELECT t.`id` as track_id, t.`isrc`, a.name as artist_name, t.name as track_name
                FROM `track` t
                    inner join artist a ON a.id = t.artist_id and a.label_id = 0
                WHERE a.country_id = 1 and t.is_album = 0
                ORDER BY a.id asc";
        
        $data = Yii::$app->db->createCommand($sql)
            ->queryAll();
        
        if (empty($data)) {
            Yii::$app->session->setFlash('error', 'Дані не знайдені');
            $this->redirect(['track/index']);
        }
        
        $tempData[] = [
            'Трек ID',
            'ISRC',
            'Виконавець',
            'Трек',
        ];
        
        $tempData = array_merge($tempData, $data);
        
        $spreadSheet = new Spreadsheet();
        // баланси
        $workSheet = $spreadSheet->getActiveSheet();
        $workSheet->setTitle('Список треків');
        $workSheet->getStyle('A1:J1')->getAlignment()
            ->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $workSheet->getStyle('A1:J1')->getFont()->setBold(true);
        // зберегти баланс на першому аркуші
        $workSheet->fromArray($tempData);
        $filename = "track_list.xlsx";
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadSheet);
        $writer->save(self::$homePage . 'xls/' . $filename);
        
        $this->redirect("/xls/".$filename);
    }

    #region Percentage
    public function actionPercentage(int $id): string
    {
        $searchModel = new PercentageSearch();
        $dataProvider = $searchModel->search(['track_id' => $id]);

        return $this->render('percentage', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'trackId' => $id,
        ]);

    }

    public function actionPercentageCreate(int $id)
    {
        if (Yii::$app->request->isPjax) {
            $model = new Percentage();

            if (!$model->load(Yii::$app->request->post()) || !$model->save()) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($model);
            } else {
                $models = Percentage::find()
                    ->where(['track_id' => $id])
                    ->all();

                $createModel = new Percentage();
                $createModel->track_id;

                return $this->render('percentageUpdate', [
                    'models' => $models,
                    'createModel' => $createModel,
                    'track' => Track::findOne($id),
                    'artist' => Artist::find()
                        ->select(['name', 'id'])
                        ->indexBy('id')
                        ->column()
                ]);
            }
        }

        $model = new Percentage();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['percentage-update', 'trackId' => $model->track_id]);
        }

        $model->track_id = $id;

        return $this->render('percentageCreate', [
            'model' => $model,
            'artist' => Artist::find()
                ->select(['name', 'id'])
                ->indexBy('id')
                ->column()
        ]);
    }

    public function actionPercentageUpdate(int $trackId)
    {
        $Percentage = Yii::$app->request->post('Percentage', []);
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!empty($Percentage) && is_array($Percentage)) {

            foreach ($Percentage as $type1 => $sub) {
                foreach ($sub as $type2 => $form) {
                    if ($type2 != 5) {
                        $sum = array_sum($form);

                        if ($sum != 0 && $sum != 100) {
                            // throw new \InvalidArgumentException($sum);
                            // Yii::$app->response->format = Response::FORMAT_JSON;
                            $model = new Percentage();
                            $model->percentage = $sum;
                            $validate = ActiveForm::validate($model, 'percentage');

                            if (!empty($validate)) {


                                return $validate;
                            }
                        }

                    }
                }
            }


        $res = "";

        foreach ($Percentage as $sub) {
            foreach ($sub as $form) {
                foreach ($form as $id => $percentage) {
                    $model = Percentage::findOne($id);

                    if ($model->percentage != $percentage) {
                        $temp = [
                            'id' => $id,
                            'old' => $model->percentage,
                            'new' => $percentage,
                        ];

                        $model->percentage = $percentage;
                        if($model->save()) {
                            $res .= "{$temp['id']} :<s>{$temp['old']}</s> => {$temp['new']}\n";
                        }
                    }
                }
            }
        }

        if (!empty($res)) {
            $track =  $this->findModel($trackId);
            t::log(Yii::$app->user->identity->getFullName() . "\nОнеовлено % для треку.\n $track->artist_name:$track->name ({$track->isrc})\n" .  $res);
        }
    }

        echo 'Дані збережено!';
        die;

      //  return $this->redirect(['index']);
    }

    public function actionLoadModal(int $trackId)
    {
        $data = Percentage::find()
            ->select(['track_to_percentage.id', 'track_to_percentage.track_id', 'track_to_percentage.artist_id', 'track_to_percentage.percentage',
                'artist.name as artist_name',
                'ownership.id as ownership_id', 'ownership.name as ownership_name',
                'ownership_type.id as ownership_type_id', 'ownership_type.name as type_name', ])
            ->from('track_to_percentage')
            ->innerJoin('track', 'track.id = track_to_percentage.track_id')
            ->innerJoin('artist', 'artist.id = track_to_percentage.artist_id')
            ->leftJoin('ownership_type', 'ownership_type.id = track_to_percentage.ownership_type')
            ->leftJoin('ownership', 'ownership.id = ownership_type.ownership_id')
            ->where(['track_to_percentage.track_id' => $trackId])
            ->orderBy('track_to_percentage.artist_id, ownership_type.sort')
            ->asArray()
            ->all();

        $mdata = [];

        foreach ($data as $item) {
            $mdata[$item['ownership_id']][$item['ownership_type_id']][$item['artist_name'] . ': ' . $item['type_name']] = $item;
        }

        $model = new Perc();
        $model->track_id = $trackId;
        $model->data = $mdata;


        return $this->renderAjax('../../widgets/views/___percentageModal', [
            'model' => $model,
            'track' => Track::findOne($trackId),
        ]);
    }

    public function actionPercentageDelete(int $id, int $percentageId): Response
    {
        Percentage::findOne($percentageId)->delete();

        return $this->redirect(['percentage', 'id' => $id]);
    }

    #endregion Percentage

    #region load track

    public function actionImport()
    {
        $model = new UploadReport();

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            $model->file = UploadedFile::getInstance($model, 'file');

            if (is_null($model->file)) {
                throw new \RuntimeException('Please Select xls File');
            }

            $reader = new Xlsx();

            try {
                $spreadsheet = $reader->load($model->file->tempName);
            } catch (Throwable $e) {
                die($e->getMessage());
            }

            $worksheet = $spreadsheet->getActiveSheet();
            $importResults = $worksheet->toArray();
            unset($importResults[0]);

            $errorTrack = [];
            $errorArtist = [];
            $foundTrack = 0;
            $addedTrack = 0;
            $addedArtist = 0;
            
            $result = [];

            foreach ($importResults as $item) {
                $isrc = str_replace("-", "", trim($item[0]));

                $isrcObject = new Isrc($isrc);
                $temp = [
                    'isrc' => $isrc,
                    'track_name' => trim($item[1]),
                    'artist_name' => $item[2],
                    'artist_alias' => trim($item[3]),
                    'sub_label' => $item[4],
                    'import_status' => [],
                ];

                if (!$isrcObject->isValid(true)) {
                    $temp['import_status'][] = 'invalid isrc';
                    $result[] = $temp;
                    continue;
                }

                $isrc = $isrcObject->getIsrc(false);
                
                $track = Track::getTrackByIsrc($isrc);

                if (!is_null($track)) {
                    $temp['track_name'] = $track->name;
                    $temp['artist_name'] = $track->artist->full_name;
                    $temp['artist_alias'] = $track->artist->name;
                    $temp['sub_label'] = $track->artist->label->name;
                    
                    $foundTrack++;
                    continue;
                }

                if ($item[5] > 0) {
                    $artist = Artist::findOne(['id' => (int)$item[5], 'active'=> 1]);
                } else if ($item[4] > 0) {
                    $artist = Artist::findOne(['label_id' => (int)$item[4], 'active'=> 1]);
                } else {
                    $artist = Artist::getArtistByName(trim($item[3]), $item[4]);
                }
                
                if (is_null($artist) && $item[4] == 0) {
                    $artist = new Artist();
                    $artist->name = mb_strlen(trim($item[3])) > 150 ? substr(trim($item[3]), 0, 150) : trim($item[3]);
                    $artist->percentage = 70;
                    $artist->label_id = 0;
                    $artist->type_id = 1;
                    $artist->artist_type_id = 1;
                    $artist->admin_id = 16;
                    $artist->full_name = mb_strlen(trim($item[2])) > 150 ? substr(trim($item[2]), 0, 150) : trim($item[2]);

                    if (!$artist->save()) {
                        $temp['import_status'][] = 'error add artist';
                        $result[] = $temp;
                        //$errorArtist[] = $item;
                        continue;
                    }

                   // $addedArtist++;
                }

                if (is_null($artist)) {
                    $temp['import_status'][] = 'error add artist';
                    $result[] = $temp;
                    continue;
                }

                $track = new Track();
                $track->isrc = $isrc;
                $track->admin_id = 16;
                $track->artist_id = $artist->id;
                $track->artist_name = $temp['artist_alias'];
                $track->name = trim($item[1]);
                    $temp['artist_name'] = $artist->full_name;
                    $temp['sub_label'] = $artist->label->name;
                $track->img = '2565_XZEVWO7R.jpg';
                $track->is_album = 0;
                $url = trim(Yii::$app->translit->t($track->name));
                $bytes = random_bytes(3);
                $track->url = substr($url, 0, 48) . bin2hex($bytes);
                $track->servise = serialize([]);

                if(!$track->save()) {
                    $temp['import_status'] =  $track->getFirstErrors();
                    $result[] = $temp;
                   // $errorTrack[] = $item;
                    continue;
                }

               $addedTrack++;

                if ($track->artist->type_id == 1) {
                    $track->addArtistPercentage();
                }
            }
        }

        return $this->render(
            'import',
            [
                'model' => $model,
                'foundTrack' => $foundTrack ?? 0,
                'addedTrack' => $addedTrack ?? 0,
                'result' => $result ?? [],
                //'addedArtist' => $addedArtist ?? 0,
            ]
        );

    }
    #endregion load track

    /**
     * Перерахунок всіх попередніх нарахувань по інвойсам для конкретного треку
     * відповідно до поточних відсотків артиста.
     *
     * @param int $id Track ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionRecalculateInvoices(int $id): \yii\web\Response
    {
        $track = $this->findModel($id);

        // Знаходимо всі debit-інвойси (тип 1 = Нарахування), що мають items для цього треку
        // і мають статус Calculated (2) або InProgress (4)
        $invoices = Invoice::find()
            ->innerJoin('invoice_items', 'invoice_items.invoice_id = invoice.invoice_id')
            ->where(['invoice_items.track_id' => $id])
            ->andWhere(['invoice.invoice_type' => InvoiceType::$debit])
            ->andWhere(['not', ['invoice.aggregator_report_id' => null]])
            ->andWhere(['in', 'invoice.invoice_status_id', [InvoiceStatus::Calculated, InvoiceStatus::InProgress]])
            ->groupBy('invoice.invoice_id')
            ->orderBy('invoice.invoice_id ASC')
            ->all();

        if (empty($invoices)) {
            Yii::$app->session->setFlash('warning', 'Не знайдено розрахованих інвойсів для цього треку');
            return $this->redirect(['view', 'id' => $id]);
        }

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            $recalcCount = 0;

            foreach ($invoices as $invoice) {
                // Отримуємо суму з агрегаторського звіту для цього треку в цьому інвойсі
                $reportAmount = (new \yii\db\Query())
                    ->from(AggregatorReportItem::tableName())
                    ->select('SUM(amount) as amount')
                    ->where([
                        'report_id' => $invoice->aggregator_report_id,
                        'track_id'  => $id,
                    ])
                    ->scalar();

                if ($reportAmount === null || $reportAmount === false) {
                    continue; // цей трек не входить у звіт — пропускаємо
                }

                $reportAmount = (float) $reportAmount;

                // Видаляємо старі розподілення для items цього треку в інвойсі
                $oldItemIds = (new \yii\db\Query())
                    ->from(InvoiceItems::tableName())
                    ->select('id')
                    ->where(['invoice_id' => $invoice->invoice_id, 'track_id' => $id])
                    ->column();

                if (!empty($oldItemIds)) {
                    InvoiceAllocationService::deleteAllocation($oldItemIds);
                }

                // Видаляємо UserBalance для цього інвойсу — буде перераховано нижче
                UserBalance::deleteAll(['invoice_id' => $invoice->invoice_id]);

                // Видаляємо старі invoice_items для цього треку
                InvoiceItems::deleteAll(['invoice_id' => $invoice->invoice_id, 'track_id' => $id]);

                // Рахуємо за поточними відсотками
                $calculation = $track->getCalculation($invoice->aggregator_id, $reportAmount);

                foreach ($calculation as $value) {
                    $invoiceItem = new InvoiceItems();
                    $invoiceItem->invoice_id        = $invoice->invoice_id;
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
                            'Помилка збереження item інвойсу ' . $invoice->invoice_id . ': '
                            . current(current($invoiceItem->getErrors()))
                        );
                    }
                }

                // Перераховуємо загальну суму інвойсу
                $invoice->calculate();

                // Requeue invoice for allocation rebuild after track-level changes.
                if ($invoice->allocated != 0) {
                    $invoice->allocated = 0;
                    $invoice->save(false, ['allocated']);
                }

                // Якщо статус Calculated — оновлюємо баланси користувачів
                if ($invoice->invoice_status_id == InvoiceStatus::Calculated) {
                    $invoice->calculateUser();
                }

                $recalcCount++;
            }

            // Перераховуємо депозити всіх артистів
            Artist::calculationDeposit();

            $transaction->commit();
            Yii::$app->session->setFlash(
                'success',
                "Перераховано {$recalcCount} інвойс(ів) для треку «{$track->name}»"
            );
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Помилка при перерахунку: ' . $e->getMessage());
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Finds the Track model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Track the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Track::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
