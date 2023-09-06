<?php

namespace backend\controllers;

use Yii;
use backend\models\Track;
use backend\models\TrackSearch;
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
                'class' => VerbFilter::className(),
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
    public function actionView($id)
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
if(Yii::$app->request->isAjax ){
        if ($model->load(Yii::$app->request->post())){
            Yii::$app->response->format = Response::FORMAT_JSON;
            $valid = ActiveForm::validate($model);
            if($valid){
                 return $valid;
           }
        }
      }
        if ($model->load(Yii::$app->request->post())){
            $id = Track::find()->orderBy('id DESC')->one()->id;
             $id++;
            $file = UploadedFile::getInstance($model, 'file');
            if ($file && $file->tempName) {
                $model->file = $file;
                if ($model->validate(['file'])) {
                    $model->img = Upload::createImage($model, $id, 'track', [500, 500]);
                }
            }
            if(!$model->url){
                $model->url = trim(Yii::$app->translit->t($model->name));//     Yii::$app->getSecurity()->generateRandomString(8);
            }
             $model->servise = serialize($model->servise);
            if($model->validate() && $model->save()) {

            return $this->redirect(['view', 'id' => $model->id]);
               }
           // return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }
    public function actionAnalitiks($id)
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
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        if(Yii::$app->request->isAjax ){
        if ($model->load(Yii::$app->request->post())){
            Yii::$app->response->format = Response::FORMAT_JSON;
            $valid = ActiveForm::validate($model);
            if($valid){
                 return $valid;
           }
        }
      }
          $current_image = $model->img;

        if (/*Yii::$app->request->isAjax &&*/ $model->load(Yii::$app->request->post())){
           
          // Yii::$app->response->format = Response::FORMAT_JSON;
           //  return Yii::$app->request->post();
           // $valid = ActiveForm::validate($model);
           // if($valid){
              //   
           //      return $valid;
          // }
            //&& $model->save()) {
             $file = UploadedFile::getInstance($model, 'file');
            if ($file && $file->tempName) {
               
                $model->file = $file;
                if ($model->validate(['file'])) {
                   $model->img = Upload::updateImage($model, $current_image, 'track', [500, 500]);
                }
            }else{
                $model->img = $current_image;
            }
         
           $model->servise = serialize($model->servise);
             
        if($model->validate() && $model->save()){
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
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
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
