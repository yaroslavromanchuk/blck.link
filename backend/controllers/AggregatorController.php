<?php

namespace backend\controllers;

use backend\models\ImportFile;
use backend\models\Track;
use backend\models\UploadReport;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use RuntimeException;
use Throwable;
use Yii;
use backend\models\Aggregator;
use backend\models\AggregatorReport;
use backend\models\AggregatorReportItem;
use backend\models\AggregatorSearch;
use yii\bootstrap\ActiveForm;
use yii\db\Connection;
use yii\db\Query;
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
    {//ini_set('memory_limit', '2048M');
     // set_time_limit(0);
        $model = new UploadReport();

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            $model->file = UploadedFile::getInstance($model, 'file');

            if (is_null($model->file)) {
                throw new RuntimeException('Please Select CSV File');
            }

            try {
                if ($model->file->extension === 'csv') {
                    if (($file_data = fopen($model->file->tempName, "r")) !== FALSE) {
                        $file_header = fgetcsv($file_data);
                        $importResults = [];

                        while (($row = fgetcsv($file_data)) !== FALSE) {
                            $importResults[] = $row;
                        }

                        fclose($file_data);
                    } else {
                        throw new InvalidArgumentException('Only <b>.csv</b> file allowed');
                    }
                } else {
                    $reader = new Xlsx();
                    $spreadsheet = $reader->load($model->file->tempName);
                    $worksheet = $spreadsheet->getActiveSheet();
                    $importResults = $worksheet->toArray();

                    $file_header = $importResults[0] ?? [];

                    unset($importResults[0]);
                }

                if (empty($importResults)) {
                    throw new InvalidArgumentException('Не вдалось прочитати файл');
                }

               Yii::$app->cache->set('file_data', [
                    'aggregator_id' => $model->aggregatorId,
                    'quarter' => $model->quarter,
                    'year' => $model->year,
                    'data' => $importResults,
                ], 600);

                /*
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
                }*/
            } catch (Throwable $e) {
                throw new RuntimeException($e->getMessage());
           }

            if (count($importResults) > 10) {
                $importResults = array_slice($importResults, 0, 10);
            }

            return $this->renderAjax('temp-upload', [
                'count_header' => count($file_header),
                'file_data' => $importResults,
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

            $header = Yii::$app->request->post();
            $header = array_flip($header);

            $isrc = Yii::$app->request->post('isrc');
            $date_report = Yii::$app->request->post('date_report');
            $platform = Yii::$app->request->post('platform');
            $count = Yii::$app->request->post('count');
            $amount = Yii::$app->request->post('amount');
            $country = Yii::$app->request->post('country');

            $total = 0;
            $data = [];

            $foundBroma = [];

            if ($cache['aggregator_id'] == 2) {
                $foundBroma = (new Query())
                    ->select(['isrc', 'number'])
                    ->from('broma')
                    ->indexBy('number')
                    ->column();
            }

            foreach ($file_data as $row) {
                $isr = str_replace("-", "", trim($row[$isrc]));

                if ($cache['aggregator_id'] == 2) {
                   $isr = $foundBroma[$isr] ?? $isr;
                }

                $_country = trim($row[$country]);
                $platforma = $row[$platform];

                if (empty($platforma)) {
                    $platforma = 'Загальний';
                }

                $date_r = date('Y-m-d', strtotime($row[$date_report]));

                $data[$date_r][$_country][$platforma][$isr][] = [
                    $header[$date_report] => $date_r,
                    $header[$count] => (int)$row[$count],
                    $header[$amount] => (double)$row[$amount],
                    $header[$country] => $row[$country],
                ];

                $total += round((double)$row[$amount], 4);
            }

            $data2 = [];
            if (!empty($data)) {
                $modelReport = new AggregatorReport();
                $modelReport->load([
                    'AggregatorReport' => [
                        'aggregator_id' => $cache['aggregator_id'],
                        'quarter' => $cache['quarter'],
                        'year' => $cache['year'],
                        'user_id' => Yii::$app->user->getId(),
                        'total' => round($total, 4),
                    ]
                ]);

                $modelReport->save();
                foreach ($data as $date_report => $countries) {
                    foreach ($countries as $country => $platforms) {
                        foreach ($platforms as $p_name => $isrcs) {
                            foreach ($isrcs as $isrc => $items) {
                                $temp_value = [
                                    'report_id' => $modelReport->id,
                                    'isrc' => $isrc,
                                    'platform' => $p_name,
                                    'date_report' => $date_report,
                                    'country' => $country,
                                ];

                                $c = 0;
                                $a = 0;

                                foreach ($items as $item) {
                                    if (empty($temp_value['date_report'])) {
                                        $temp_value['date_report'] = $item['date_report'];
                                    }

                                    $c += (int) $item['count'];
                                    $a += round($item['amount'], 4);
                                }

                                $temp_value['count'] = $c;
                                $temp_value['amount'] = $a;

                                $data2[] = $temp_value;
                            }
                        }
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

    public function actionIsrc()
    {
        ini_set('memory_limit', '2048M');
        $model = new ImportFile();
        $importResults = [];

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            $model->file = UploadedFile::getInstance($model, 'file');

            if (is_null($model->file)) {
                Yii::$app->session->setFlash('error', 'Виберіть xlsx файл');

                return $this->render('import',
                    [
                        'model' => $model,
                        'result' => $importResults,
                    ]);
            }

            try {
                if ($model->file->extension === 'csv') {
                    if (($file_data = fopen($model->file->tempName, "r")) !== FALSE) {
                        $file_header = fgetcsv($file_data);

                        while (($row = fgetcsv($file_data)) !== FALSE) {
                            $importResults[] = $row;
                        }

                        fclose($file_data);
                    }
                } else {
                    $reader = new Xlsx();
                    $spreadsheet = $reader->load($model->file->tempName);
                    $worksheet = $spreadsheet->getActiveSheet();
                    $importResults = $worksheet->toArray();
                    $file_header = $importResults[0] ?? '';
                }

                if (empty($importResults)) {
                    Yii::$app->session->setFlash('error', 'Не вдалось прочитати файл');

                    return $this->render('import',
                        [
                            'model' => $model,
                            'result' => $importResults,
                        ]);
                }


               // unset($importResults[0]);

              //  Yii::$app->cache->set('import_data', $importResults, 600);
            } catch (Throwable $e) {
                Yii::$app->session->setFlash('error', $e->getMessage());

                return $this->render('import',
                    [
                        'model' => $model,
                        'result' => $importResults,
                    ]);
            }

            $foundBroma = [];

            if ($model->isBroma) {
                $foundBroma = (new Query())
                    ->select(['isrc', 'number'])
                    ->from('broma')
                    ->indexBy('number')
                    ->column();
            }

            $i= 0;

                foreach ($importResults as $key => $result) {
                    if (empty($result[0]) && empty($result[1])) {
                     //   unset($importResults[$key]);
                        $i++;
                        continue;
                    }

                    if ($i === 0) {
                        $i++;
                        continue;
                    }

                    if (empty($result[7])) {
                        if ($model->isBroma) {
                            if (key_exists($result[8], $foundBroma)) {
                                $is = $foundBroma[$result[8]];
                            } else {
                                $is = $this->foundIsrcByNumberBroma($result[8]);
                            }

                            if (!empty($is)) {
                                if (!key_exists($result[8], $foundBroma)) {
                                    $foundBroma[$result[8]] = $is;
                                }

                                $importResults[$key][7] = $is;
                                $i++;
                                continue;
                            }
                        }

                        if (!empty($result[0])) {
                            $is = $this->foundIsrcTrackName($result[1], $result[0]);

                            if (!empty($is)) {
                                $importResults[$key][7] = $is;
                            }
                        }
                    } else if ($model->isBroma && !empty($result[8])) {
                        $is = $this->foundIsrcByNumberBroma($result[8]);
                        if (empty($is)) {
                            $sql = "insert into broma (number, isrc) values (:number, :isrc)";

                            $parameters = array(":number"=>trim($result[8]), ":isrc" => trim($result[7]) );

                            Yii::app()->db->createCommand($sql)->execute($parameters);
                        }
                    }

                    $i++;
                }

          //  var_dump($importResults);

            //@unlink('uploads/' . $model->file->baseName . '.' . $model->file->extension);

           // if (count($importResults) > 10) {
            //    $importResults = array_slice($importResults, 0, 10);
           // }
          //  exit;
            if (false) {
               // $spreadSheet = new Spreadsheet();
               // $workSheet = $spreadSheet->getActiveSheet();
               // $workSheet->setTitle('Баланс');
                //$tempData = [];
               // $tempData[] = $file_header;

                header("Content-Type:application/csv");
                header("Content-Disposition:attachment;filename={$model->file->baseName}_2.csv");
                $file = fopen("php://output", 'r+');
               // fputcsv($file, $file_header, ',');

                foreach ($importResults as $key => $row) {
                   // $tempData[] = $row;
                    fputcsv($file, $row, ',', '"');
                }
               /* $workSheet->fromArray($tempData, null, 'A1');
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadSheet);

                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header("Content-Disposition: attachment;filename={$model->file->baseName}_2" . time() . ".xlsx");
                header('Cache-Control: max-age=0');
                exit($writer->save('php://output'));*/
              //  rewind($file);
               // $csv = fgets($outstream);
                fclose($file);
            }
        }


        return $this->render('import',
            [
                'model' => $model,
                'result' => $importResults,
            ]
        );
    }

    private function foundIsrcTrackName(string $trackName, string $artistName)
    {
        $isrc = (new Query())
            ->select('isrc')
            ->from('track')
            ->where(['like', 'name', mb_strtolower(trim($trackName)), false])
            ->andWhere(['like', 'artist_name', mb_strtolower(trim($artistName)), false])
            ->limit(1)
            ->one();

        if (isset($isrc['isrc'])) {
            return $isrc['isrc'];
        }

        return '';
    }

    private function foundIsrcByNumberBroma(string $number)
    {
        $isrc = (new Query())
            ->select('isrc')
            ->from('broma')
            ->where(['like', 'number', mb_strtolower(trim($number)), false])
            ->limit(1)
            ->one();

        if (isset($isrc['isrc'])) {
            return $isrc['isrc'];
        }

        return '';
    }
    #endregion
}
