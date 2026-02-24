<?php

namespace backend\controllers;

use backend\models\Albums;
use backend\models\AlbumSearch;
use backend\models\Upload;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\bootstrap\ActiveForm;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\filters\AccessControl;

/**
 * AlbumController implements the CRUD actions for Albums model.
 */
class AlbumController extends Controller
{
    /**
     * @inheritDoc
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::className(),
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
                            'actions' => ['index', 'view',],
                            'roles' => ['@'], // '?' – гість, '@' – автентифікований
                        ],
                        // Створення/оновлення тільки для залогінених
                        [
                            'allow' => true,
                            'actions' => ['view', 'create', 'update', 'modal'],
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
            ]
        );
    }

    /**
     * Lists all Albums models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $searchModel = new AlbumSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Albums model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Albums model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Albums();
        
        if(Yii::$app->request->isAjax) {
            if ($model->load(Yii::$app->request->post())){
                Yii::$app->response->format = Response::FORMAT_JSON;
                
                return ActiveForm::validate($model);
            }
            
            return false;
        }

        if ($model->load(Yii::$app->request->post())) {
            $file = UploadedFile::getInstance($model, 'file');
            
            if ($file && $file->tempName) {
                $model->file = $file;
                $id = Albums::find()
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
                $model->url = trim(Yii::$app->translit->t($model->name));
            }
            
            $model->servise = serialize($model->servise);
            
            if ($model->validate() && $model->save()) {
                
                $trackIds = Yii::$app->request->post('Albums')['tracks']?? [];
                
                if (is_string($trackIds)) {
                    $trackIds = [];
                }
                
                if (!empty($trackIds) && is_array($trackIds)) {
                    $model->saveTracks($trackIds);
                }
                
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }
	
	public function actionModal()
	{
		$model = new Albums();
		
		if (Yii::$app->request->isAjax) {
			if ($model->load(Yii::$app->request->post())) {
				$model->admin_id = Yii::$app->user->identity->id;
				Yii::$app->response->format = Response::FORMAT_JSON;
				
				return ActiveForm::validate($model);
			}
			
			return true;
		}
		
		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			return $this->redirect(['view', 'id' => $model->id]);
			//return $this->goBack(Yii::$app->request->post('redirectUrl'));
		}
	}

    /**
     * Updates an existing Albums model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load(Yii::$app->request->post())) {
            $file = UploadedFile::getInstance($model, 'file');
            if ($file && $file->tempName) {
                $model->file = $file;
                if ($model->validate('file')) {
                    $model->img = Upload::updateImage($model, $model->img, 'track', [500, 500]);
                }
            }
            
            if (is_array($model->servise)) {
                $model->servise = serialize($model->servise);
            } else {
                $model->servise = serialize([]);
            }
            
            if ($model->validate() && $model->save()) {
                $trackIds = Yii::$app->request->post('Albums')['tracks']?? [];
                
                if (is_string($trackIds)) {
                    $trackIds = [];
                }
                
                if (!empty($trackIds) && is_array($trackIds)) {
                     $model->saveTracks($trackIds);
                }
                
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Albums model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        foreach ($model->getTracks()->all() as $track) {
            $track->album_id = null;
            $track->save();
        }
        
        $model->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Albums model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Albums the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Albums::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
