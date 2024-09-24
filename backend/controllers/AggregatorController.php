<?php

namespace backend\controllers;

use backend\models\Track;
use backend\models\UploadReport;
use InvalidArgumentException;
use Yii;
use backend\models\Aggregator;
use backend\models\AggregatorReport;
use backend\models\AggregatorReportItem;
use backend\models\AggregatorSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\web\UploadedFile;

/**
 * AggregatorController implements the CRUD actions for Aggregator model.
 */
class AggregatorController extends Controller
{

    const CacheReportId = 'CacheReportId_';
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
     * Lists all Aggregator models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new AggregatorSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Aggregator model.
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
     * Creates a new Aggregator model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Aggregator();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->aggregator_id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Aggregator model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->aggregator_id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Aggregator model.
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
     * Finds the Aggregator model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Aggregator the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Aggregator::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }

    #region upload file

    public function actionUploadReport()
    {
        $model = new UploadReport();

        if (Yii::$app->request->isPost) {

            $html = '';
            $error = '';
            $model->file = UploadedFile::getInstance($model, 'file');

            if (null !== $model->file) {
                try {
                    if (($file_data = fopen($model->file->tempName, "r")) !== FALSE) {
                        $file_header = fgetcsv($file_data);
                        $temp_data = [];

                        while(($row = fgetcsv($file_data)) !== FALSE)
                        {
                            $temp_data[] = $row;
                        }

                        fclose($file_data);

                        Yii::$app->cache->set('file_data', [
                            'aggregator_id' => $this->request->post('UploadReport')['aggregatorId'],
                            'data' => $temp_data,
                        ], 600);
                    } else {
                        $error = 'Only <b>.csv</b> file allowed';
                    }
                } catch (\Throwable $e) {
                    throw new \RuntimeException($e->getMessage());
                }

            } else {
                $error = 'Please Select CSV File';
            }

            $temp_data = array_chunk($temp_data?? [], 10);

            return $this->renderAjax('temp-upload', [
                'count_header' => count($file_header?? []),
                'file_data' => $temp_data[0]??  [],
            ]);
        }

        return $this->render('upload', ['model' => $model]);
    }

    public function actionUploadImport()
    {
        if (Yii::$app->request->isPost) {
            $cache =  Yii::$app->cache->get('file_data');
            $file_data = $cache['data'] ?? [];

            $data = [];
            $header = Yii::$app->request->post();
            unset($header['argregator_id']);

            $header = array_flip($header);

            $isrc = Yii::$app->request->post('isrc');
            $date_report = Yii::$app->request->post('date_report');
            $platform = Yii::$app->request->post('platform');
            //$artist = Yii::$app->request->post('artist');
            //$releas = Yii::$app->request->post('releas');
            //$track = Yii::$app->request->post('track');
            $count = Yii::$app->request->post('count');
            $amount = Yii::$app->request->post('amount');

            $total = 0;

            if (empty($file_data)) {
                echo json_encode([
                    'message' => 'Відсутні дані звіту в сесії',
                    'data' => $data,
                ]);
                die;
            }

            foreach($file_data as $row)
            {
                $data[] = [
                    $header[$isrc] => $row[$isrc],
                    $header[$date_report] => date('Y-m-d', strtotime($row[$date_report])),
                    $header[$platform] => $row[$platform],
                    //$header[$artist] => $row[$artist],
                    //$header[$releas] => $row[$releas],
                    //$header[$track] => $row[$track],
                    $header[$count] => $row[$count],
                    $header[$amount] => $row[$amount],
                ];

                $total += (float) $row[$amount];
            }

            if(isset($data)) {
                $modelReport = new AggregatorReport();
                $modelReport->load(['AggregatorReport' => [
                    'aggregator_id'=> Yii::$app->request->post('argregator_id'),
                    'user_id'=> Yii::$app->user->getId(),
                    'total' => $total,
                ]]);

                $modelReport->save();
               // $insertData = [];
               // $connection = Yii::$app->db;

                try {
                    foreach ($data as $insert) {

                        if (empty($insert['platform'])) {
                            $insert['platform'] = 'Загальний';
                        }

                        $track = Track::getTrackByIsrc($insert['isrc']);

                        if (!is_null($track)) {
                            if (empty($insert['artist'])) {
                                $insert['artist'] = $track->artist_name;
                            }

                            if (empty($insert['track'])) {
                                $insert['track'] = $track->name;
                            }

                        } else {
                            $insert['artist'] = 'empty';
                            $insert['track'] = 'empty';
                        }

                        if (empty($insert['count'])) {
                            $insert['count'] = 0;
                        }

                        $insert['report_id'] = $modelReport->id;

                        //$connection->createCommand()
                         //   ->insert('aggregator_report_item', $insert)
                         //   ->execute();

                        $model = new AggregatorReportItem();
                        $model->load(['AggregatorReportItem' => $insert]);
                        //$insertData[] = $insert;
                        if (!$model->save(false)) {
                            $errors = $model->getErrors();
                            Yii::$app->session->setFlash('error', current($errors));

                            print_r($insert);
                            print_r($errors);
                            throw new InvalidArgumentException('Помилка збереження даних звіту');
                        }
                    }

                } catch (\Throwable $e) {
                    $modelReport->delete();

                    echo json_encode([
                        'message' => 'Помилка збереження даних звіту: ' . $e->getMessage(),
                       // 'data' => $data,
                    ]);
                    die;
                }

                return $this->redirect(['/aggregator-report/view', 'id' => $modelReport->id]);
            } else {
                echo json_encode([
                    'message' => 'Помилка завантаження',
                    'data' => $data,
                ]);
            }
        } else {
            echo json_encode([
                'message' => 'Помилка ',
                'data' => [],
            ]);
        }

        Yii::$app->cache->delete('file_data');

        die;

    }
    #endregion
}
