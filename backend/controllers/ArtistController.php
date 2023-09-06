<?php

namespace backend\controllers;

use Yii;
use backend\models\Artist;
use backend\models\ArtistSearch;
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
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
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

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
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

		if (Yii::$app->request->isAjax ) {
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
          //  return $this->redirect(['view', 'id' => $model->id]);
        

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
