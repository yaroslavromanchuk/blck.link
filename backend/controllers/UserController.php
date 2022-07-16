<?php

namespace backend\controllers;

use Yii;
use backend\models\User;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\UploadedFile;
use yii\imagine\Image;
use Imagine\Gd;
use Imagine\Image\Box;
use Imagine\Image\BoxInterface;


/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
		'access' => [
                'class' => AccessControl::className(),
                'only' => ['index', 'create', 'update', 'view', 'delete' ],
                'rules' => [
                    
                    [
                        'actions' => ['error', 'update', 'view'],
                        'allow' => true,
                        'roles' => ['@'],
                        
                    ],
                   /* [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => ['manager']
                    ],
                    [
                        'actions' => ['index','delete'],
                        'allow' => true,
                        'roles' => ['moder']
                    ],*/
                    [
                        'actions' => ['index', 'create', 'delete'],
                        'allow' => true,
                        'roles' => ['admin']
                    ],
                    
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all User models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => User::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single User model.
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
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new User();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
         
        $model = $this->findModel($id);
        $current_image = $model->logo;
        if ($model->load(Yii::$app->request->post())) {

            $file = UploadedFile::getInstance($model, 'file');
            if ($file && $file->tempName) {
               
                $model->file = $file;
                if ($model->validate(['file'])) {
                    $dir = Yii::getAlias('@app/../frontend/web/images/user/');
                        if(file_exists($dir.$current_image))
                        {
                            //удаляем файл
                            @unlink($dir.$current_image);
                        }     
                  $fileName = $model->id.'.' . $model->file->extension;
                  $img = $dir . $fileName;
                    $model->file->saveAs($img);
                    $model->file = $fileName; // без этого ошибка

                    $mig =  Image::getImagine()->open($img);
                    $size = $mig->getSize();
                    $ratio = $size->getWidth()/$size->getHeight();

                    $width = 60;
                    $height = round($width/$ratio);
                   $mig->thumbnail(new Box($width, $height))->save($img, ['quality' => 90]);

                   $model->logo = $fileName;
                }
            }else{
                $model->logo = $current_image;
            }
            
            if($model->pass){
                $model->setPassword($model->pass);
            }
            
            if($model->save()){
            return $this->redirect(['view', 'id' => $model->id]);
            }else{
                return $this->render('update', [
            'model' => $model,
        ]);
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing User model.
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
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
		
        if (($model = User::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
