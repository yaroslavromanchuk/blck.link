<?php

namespace backend\controllers;

use backend\models\Track;
use backend\models\UploadReport;
use InvalidArgumentException;
use Throwable;
use Yii;
use backend\models\Aggregator;
use backend\models\AggregatorReport;
use backend\models\AggregatorReportItem;
use backend\models\AggregatorSearch;
use yii\bootstrap\ActiveForm;
use yii\db\Connection;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\web\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

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

    /**
     * завантаження звіту в кеш, пред подготовка до імпорту
     *
     */
    public function actionUploadReport()
    {
        ini_set('memory_limit', '1024M');
        $model = new UploadReport();

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            $model->file = UploadedFile::getInstance($model, 'file');

           // if (!$model->upload()) {
           //     return false;
           // }

            if (is_null($model->file)) {
                throw new \RuntimeException('Please Select CSV File');
            }

            //var_dump($model->file); exit;
            /*
            $reader = new Xlsx();
            try {
                $spreadsheet = $reader->load('uploads/' . $model->file->baseName . '.' . $model->file->extension);

            } catch (Throwable $e) {
                die($e->getMessage());
            }

            $worksheet = $spreadsheet->getActiveSheet();
            $importResults = $worksheet->toArray();

            $file_header = array_keys(current($importResults));
            */

            try {
                if (($file_data = fopen($model->file->tempName, "r")) !== FALSE) {
                    $file_header = fgetcsv($file_data);
                    $importResults = [];

                    while(($row = fgetcsv($file_data)) !== FALSE)
                    {
                        $importResults[] = $row;
                    }

                    fclose($file_data);

                    Yii::$app->cache->set('file_data', [
                        'aggregator_id' => $model->aggregatorId,
                        'quarter' => $model->quarter,
                        'year' => $model->year,
                        'data' => $importResults,
                    ], 600);
                } else {
                    throw new InvalidArgumentException('Only <b>.csv</b> file allowed');
                }
            } catch (Throwable $e) {
                throw new \RuntimeException($e->getMessage());
           }

            //@unlink('uploads/' . $model->file->baseName . '.' . $model->file->extension);
            $importResults = array_chunk($importResults?: [], 10);

            return $this->renderAjax('temp-upload', [
                'count_header' => count($file_header ?: []),
                'file_data' => $importResults[0]?:  [],
            ]);
        }

        return $this->render('upload', ['model' => $model]);
    }

    /**
     * Завантаження звіту в БД
     *
     * @return void|Response
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionUploadImport()
    {
        if (Yii::$app->request->isPost) {
            $cache =  Yii::$app->cache->get('file_data');
            $file_data = $cache['data'] ?? [];

            if (empty($file_data)) {
                echo json_encode([
                    'message' => 'Відсутні дані звіту в сесії',
                ]);

                die;
            }

            $data = [];
            $header = Yii::$app->request->post();
            $header = array_flip($header);

            $isrc = Yii::$app->request->post('isrc');
            $date_report = Yii::$app->request->post('date_report');
            $platform = Yii::$app->request->post('platform');
            $count = Yii::$app->request->post('count');
            $amount = Yii::$app->request->post('amount');

            $total = 0;

            foreach ($file_data as $row) {
                $isr = trim($row[$isrc]);
                $platforma = $row[$platform];

                if (empty($platforma)) {
                    $platforma = 'Загальний';
                }

                $data[$isr][$platforma][] = [
                    $header[$date_report] => date('Y-m-d', strtotime($row[$date_report])),
                    $header[$count] => $row[$count],
                    $header[$amount] => $row[$amount],
                ];

                $total += (float) $row[$amount];
            }

            $data2 = [];

            if (!empty($data)) {
                $modelReport = new AggregatorReport();
                $modelReport->load([
                    'AggregatorReport' => [
                        'aggregator_id'=> $cache['aggregator_id'],
                        'quarter'=> $cache['quarter'],
                        'year'=> $cache['year'],
                        'user_id'=> Yii::$app->user->getId(),
                        'total' => round($total, 4),
                    ]
                ]);

                $modelReport->save();

                foreach ($data as $isrc => $platforms) {
                    foreach ($platforms as $name => $items) {
                        $temp_value = [
                            'report_id' => $modelReport->id,
                            'isrc' => $isrc,
                            'platform' => $name,
                            'date_report' => '',
                        ];

                        $c = 0;
                        $a = 0;

                        foreach ($items as $item) {
                            if (empty($temp_value['date_report'])) {
                                $temp_value['date_report'] = $item['date_report'];
                            }

                            $c += (float) $item['count'];
                            $a += (float) $item['amount'];
                        }

                        $temp_value['count'] = $c;
                        $temp_value['amount'] = $a;

                        $data2[] = $temp_value;
                    }
                }


            if(!empty($data2)) {
                try {
                    Yii::$app
                        ->db
                        ->createCommand()
                        ->batchInsert(
                            AggregatorReportItem::tableName(),
                            array_keys(current($data2)),
                            $data2
                        )
                        ->execute();
                } catch (\Throwable $e) {
                    $modelReport->delete();

                    echo json_encode([
                        'message' => 'Помилка збереження даних звіту: ' . $e->getMessage(),
                    ]);

                    die;
                }

                return $this->redirect(['/aggregator-report/view', 'id' => $modelReport->id]);
            } else {

                $modelReport->delete();

                echo json_encode([
                    'message' => 'Помилка завантаження',
                ]);
            }
            }
        } else {
            echo json_encode([
                'message' => 'Помилка ',
            ]);
        }

        Yii::$app->cache->delete('file_data');

        die;

    }
    #endregion
}
