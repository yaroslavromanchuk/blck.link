<?php

namespace backend\controllers;

use backend\models\Perc;
use backend\models\Percentage;
use backend\models\PercentageSearch;
use backend\models\ReleaseSearch;
use common\models\t;
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

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
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

		if(Yii::$app->request->isAjax ) {
        	if ($model->load(Yii::$app->request->post())){
            	Yii::$app->response->format = Response::FORMAT_JSON;

        	    return ActiveForm::validate($model);
       		}
        	return true;
      }

        if ($model->load(Yii::$app->request->post())) {
            $id = Track::find()
                ->orderBy('id DESC')
                ->one()
                ->id;
             $id++;

            $file = UploadedFile::getInstance($model, 'file');

            if ($file && $file->tempName) {
                $model->file = $file;

                if ($model->validate(['file'])) {
                    $model->img = Upload::createImage($model, $id, 'track', [500, 500]);
                }
            }

            if (empty($model->url)) {
                $model->url = trim(Yii::$app->translit->t($model->name));//     Yii::$app->getSecurity()->generateRandomString(8);
            }

             $model->servise = serialize($model->servise);

            if($model->validate() && $model->save()) {
                $model->addArtistPercentage();

                $feeds = Yii::$app->request->post('Track')['feeds']?? [];

                if (!empty($feeds) && is_array($feeds)) {
                    $model->saveFeeds(Yii::$app->request->post('Track')['feeds']?? []);
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

                if ($model->validate(['file'])) {
                   $model->img = Upload::updateImage($model, $model->img, 'track', [500, 500]);
                }
            }
         
           $model->servise = serialize($model->servise);
             
			if ($model->validate() && $model->save()) {

                if (count(Percentage::findAll(['track_id' => $model->id, 'artist_id' => $model->artist_id])) != 4) {
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

        return $this->redirect(['index']);
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
        if (Yii::$app->request->isPjax) {
            foreach (Yii::$app->request->post('Percentage') as $sub) {
                foreach ($sub as $form) {
                    foreach ($form as $id => $percentage) {
                        $model = Percentage::findOne($id);
                        $model->percentage = $percentage;
                        $model->save();
                    }
                }
            }

            t::log('User: '.Yii::$app->user->identity->getFullName().', update %, track ID: ' . $trackId);


            // $model = Percentage::findOne($id);

           // Yii::$app->response->format = Response::FORMAT_JSON;

           /* if (!$model->load(Yii::$app->request->post()) || !$model->save()) {
                Yii::$app->response->format = Response::FORMAT_JSON;

                return ActiveForm::validate($model);
            } else {
                return $this->renderAjax('../../widgets/views/_percentageModalOne', [
                    'model' => $model
                ]);
            }*/

           // return true;
        }

        //return false;

        return $this->redirect(['index']);


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
            ->orderBy('track_to_percentage.artist_id')
            ->asArray()
            ->all();

        $mdata = [];

        foreach ($data as $item) {
            $mdata[$item['ownership_id']][$item['artist_name'] . ': ' . $item['type_name']] = $item;
        }

        $model = new Perc();
        $model->track_id = $trackId;
        $model->data = $mdata;

        return $this->renderAjax('../../widgets/views/__percentageModal', [
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
