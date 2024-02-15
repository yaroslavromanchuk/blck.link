<?php

namespace backend\controllers;

use backend\models\Percentage;
use backend\models\PercentageSearch;
use backend\models\ReleaseSearch;
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

                $pr = new Percentage();
                $pr->track_id = $model->id;
                $pr->artist_id = 0;
                $pr->percentage = 30;

                $pr->save();

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

    public function actionPercentageCreate(int $trackId)
    {

        if (Yii::$app->request->isPjax) {
            $model = new Percentage();

            if (!$model->load(Yii::$app->request->post()) || !$model->save()) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($model);
            } else {
                $models = Percentage::find()
                    ->where(['track_id' => $trackId])
                    ->all();

                $createModel = new Percentage();
                $createModel->track_id;

                return $this->render('percentageUpdate', [
                    'models' => $models,
                    'createModel' => $createModel,
                    'track' => Track::findOne($trackId),
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

        $model->track_id = $trackId;

        return $this->render('percentageCreate', [
            'model' => $model,
            'artist' => Artist::find()
                ->select(['name', 'id'])
                ->indexBy('id')
                ->column()
        ]);
    }

    public function actionPercentageUpdate(int $trackId, int $id = null)
    {
        if (Yii::$app->request->isPjax && null !== $id) {
            $model = Percentage::findOne($id);

            if (!$model->load(Yii::$app->request->post()) || !$model->save()) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($model);
            }
        }

        $models = Percentage::find()
            ->where(['track_id' => $trackId])
            ->all();

        $create = new Percentage();
        $create->track_id;

       // var_dump($createModel->toArray()); exit;

        return $this->render('percentageUpdate', [
            'models' => $models,
            'create' => $create,
            'track' => Track::findOne($trackId),
            'artist' => Artist::find()
                ->select(['name', 'id'])
                ->indexBy('id')
                ->column()
        ]);
    }

    public function actionPercentageDelete(int $id, int $percentageId): Response
    {
        Percentage::findOne($percentageId)->delete();

        return $this->redirect(['percentage', 'id' => $id]);
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

       // var_dump($model);exit();
       /* if (Yii::$app->request->isAjax) {
			if ($model->load(Yii::$app->request->post())){
				Yii::$app->response->format = Response::FORMAT_JSON;
				return ActiveForm::validate($model);
			}

			return true;
        }*/

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
