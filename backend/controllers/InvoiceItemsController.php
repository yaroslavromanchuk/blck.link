<?php

namespace backend\controllers;

use Yii;
use backend\models\InvoiceItems;
use backend\models\InvoiceItemsSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * InvoiceItemsController implements the CRUD actions for InvoiceItems model.
 */
class InvoiceItemsController extends Controller
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
     * Lists all InvoiceItems models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new InvoiceItemsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single InvoiceItems model.
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
     * Creates a new InvoiceItems model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate(int $id)
    {
        $model = new InvoiceItems();

        if (!$model->load(Yii::$app->request->post())) {
            $errors = $model->getErrors();
            Yii::$app->session->setFlash('error', current($errors));

            return $this->redirect(['invoice/view', 'id' => $id]);
        }

        $model->amount = $model->amount * -1;

        if (!$model->save()) {

            $errors = $model->getErrors();
            Yii::$app->session->setFlash('error', current($errors));

            return $this->redirect(['invoice/view', 'id' => $id]);
        }

        $model->invoice->calculate();

        return $this->redirect(['invoice/view', 'id' => $model->invoice_id]);

       // $this->redirect(['invoice/view', 'id' => $id]);

       // return $this->render('create', [
         //   'model' => $model,
       // ]);
    }

    /**
     * Updates an existing InvoiceItems model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing InvoiceItems model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id, $url = '')
    {
        $this->findModel($id)->delete();

        if (!empty($url)) {
            return $this->redirect($url);
        }

        return $this->redirect(['index']);
    }

    /**
     * Finds the InvoiceItems model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return InvoiceItems the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = InvoiceItems::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
