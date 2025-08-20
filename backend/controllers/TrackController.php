<?php

namespace backend\controllers;

use backend\models\Perc;
use backend\models\Percentage;
use backend\models\PercentageSearch;
use backend\models\ReleaseSearch;
use backend\models\SubLabel;
use backend\models\UploadReport;
use common\models\t;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Throwable;
use Yii;
use backend\models\Track;
use backend\models\Artist;
use backend\models\TrackSearch;
use yii\base\Exception;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use backend\models\Upload;
use yii\web\UploadedFile;
use yii\web\Response;
use yii\bootstrap\ActiveForm;
use yii\base\Model;

/**
 * TrackController implements the CRUD actions for Track model.
 */
class TrackController extends Controller
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
                   // 'percentage-update' => ['POST', 'GET'],
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

		if(Yii::$app->request->isAjax) {
        	if ($model->load(Yii::$app->request->post())){
            	Yii::$app->response->format = Response::FORMAT_JSON;

        	    return ActiveForm::validate($model);
       		}
        	return true;
      }

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

            $model->isrc = str_replace("-", "", trim($model->isrc));
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
                    t::log(Yii::$app->user->identity->getFullName()  . "\nДодав " . $message, 529871503);
                }

                return $this->redirect(['view', 'id' => $model->id]);
            }
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

        if ($model->load(Yii::$app->request->post())) {
             $file = UploadedFile::getInstance($model, 'file');
            if ($file && $file->tempName) {
                $model->file = $file;
                if ($model->validate('file')) {
                   $model->img = Upload::updateImage($model, $model->img, 'track', [500, 500]);
                }
            }
         
           $model->servise = serialize($model->servise);
             
			if ($model->validate() && $model->save()) {

                if (!$model->is_album && count(Percentage::findAll(['track_id' => $model->id, 'artist_id' => $model->artist_id])) != 4) {
                    $model->updateArtistPercentage();
                }

                $feeds = Yii::$app->request->post('Track')['feeds'] ?? [];

                if (is_string($feeds)) {
                    $feeds = [];
                }

                $model->saveFeeds($feeds);

                return $this->redirect(['view', 'id' => $model->id]);
			}
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


        $res = '';

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
                            $res .= implode(",", $temp) . "\n";
                        }
                    }
                }
            }
        }

        if (!empty($res)) {
            $track =  $this->findModel($trackId);
            t::log(Yii::$app->user->identity->getFullName() . "\nОнеовлено % для треку. ISRC: {$track->isrc}\n" .  $res);
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
            $foundTrack = [];
            $addedTrack = 0;
            $addedArtist = 0;

            foreach ($importResults as $item) {
                $isrc = trim($item[0]);
                $track = Track::getTrackByIsrc($isrc);

                if (!is_null($track)) {
                    $foundTrack[] = $item;
                    continue;
                }

                $artist = Artist::getArtistByName(trim($item[3]), $item[4]);

                if (is_null($artist)) {
                    $label = SubLabel::findOne($item[4]);
                    $artist = new Artist();
                    $artist->name = mb_strlen(trim($item[3])) > 150 ? substr(trim($item[3]), 0, 150) : trim($item[3]);
                    $artist->percentage = $label->percentage;
                    $artist->label_id = $label->id;
                    $artist->artist_type_id = 1;
                    $artist->admin_id = 16;
                    $artist->full_name = mb_strlen(trim($item[2])) > 150 ? substr(trim($item[2]), 0, 150) : trim($item[2]);

                    if (!$artist->save()) {
                        $errorArtist[] = $item;
                        continue;
                    }

                    $addedArtist++;
                }

                $track = new Track();
                $track->isrc = $isrc;
                $track->admin_id = 16;
                $track->artist_id = $artist->id;
                $track->artist_name = $artist->name;
                $track->name = trim($item[1]);
                $track->img = '2565_XZEVWO7R.jpg';
                $track->is_album = 0;
                $url = trim(Yii::$app->translit->t($track->name));
                $bytes = random_bytes(3);
                $track->url = substr($url, 0, 48) . bin2hex($bytes);
                $track->servise = serialize([]);

                if(!$track->validate()) {
                    print_r($track->getErrors());
                }

                if(!$track->save()) {
                    $errorTrack[] = $item;
                    continue;
                }

                $addedTrack++;

                if (!$track->isSubLabel()) {
                    $track->addArtistPercentage();
                }
            }

            echo '<pre>';
            echo 'Added artist:' . $addedArtist. PHP_EOL;
            echo 'Added track:' . $addedTrack . PHP_EOL;

            echo 'Error Artist:' . PHP_EOL;
            print_r($errorArtist);
            echo 'Error Track:' . PHP_EOL;
            print_r($errorTrack);
            echo 'Found Track:' . PHP_EOL;
            print_r($foundTrack);
            echo '</pre>';
            exit;
        }

        return $this->render('import', ['model' => $model]);

    }
    #endregion load track

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
