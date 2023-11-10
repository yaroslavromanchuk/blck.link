<?php

namespace backend\controllers;

use Yii;
use backend\models\Track;
use backend\models\Artist;
use backend\models\TrackSearch;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use backend\models\Upload;
use yii\web\UploadedFile;
use yii\web\Response;
use yii\bootstrap\ActiveForm;

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

                $this->saveFeeds($model->id, Yii::$app->request->post('Track')['feeds']?? []);

            	return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }
    public function actionAnalitiks(int $id)
    {
        $model = $this->findModel($id);
        
        return $this->render('analitiks', [
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
                $this->saveFeeds($model->id, Yii::$app->request->post('Track')['feeds']?? []);

                return $this->redirect(['view', 'id' => $model->id]);
			}
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    private function saveFeeds($trackId, array $data)
    {
        $db = ActiveRecord::getDb();

        $db->createCommand('DELETE FROM `feeds_mapping` WHERE track_id = :id', [':id' => $trackId])->execute();

        foreach ($data as $item) {
            $db->createCommand()->insert('feeds_mapping',
                [
                    'track_id' => $trackId,
                    'artist_id' => (int)$item,
                ]
            )->execute();
        }



      //  $db->createCommand()->insert('feeds_mapping', $insert)->execute();

       /* $db->createCommand('INSERT INTO `feeds_mapping` (track_id, artist_id) VALUES (:track_id, :artist_id)')
            ->bindValues($insert)
            ->execute();*/
    }

	/**
	 * Deletes an existing Track model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param integer $id
	 * @return Response
     * @throws NotFoundHttpException if the model cannot be found
	 * @throws StaleObjectException
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
            $model->feeds = Artist::find()
                ->select(['artist.id'])
                ->leftJoin('feeds_mapping', 'feeds_mapping.artist_id = artist.id')
                ->where(['feeds_mapping.track_id' => $model->id])
                ->asArray()
                ->all();

            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
