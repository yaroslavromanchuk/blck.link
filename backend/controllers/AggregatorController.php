<?php

namespace backend\controllers;

use backend\helpers\Isrc;
use backend\models\Aggregator;
use backend\models\AggregatorReport;
use backend\models\AggregatorReportItem;
use backend\models\AggregatorSearch;
use backend\models\AggregatorToOwnershipType;
use backend\models\ImportFile;
use backend\models\UploadReport;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use yii\db\Query;

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
        $model->ownership_types = array_column(
                $model->aggregatorToOwnershipTypes,
                'ownership_type_id'
        );
        
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            
            $ownership_types = Yii::$app->request->post('Aggregator')['ownership_types'] ?? [];
            
            if (!is_array($ownership_types)) {
                $ownership_types = [];
            }
            
            $model->ownership_types = $ownership_types;
            
            /** видаляємо старі звʼязки */
            AggregatorToOwnershipType::deleteAll([
                'aggregator_id' => $model->aggregator_id,
            ]);
            
            if (!empty($model->ownership_types)) {
                /** зберігаємо нові */
                foreach ($model->ownership_types as $ownershipId) {
                    $rel = new AggregatorToOwnershipType();
                    $rel->aggregator_id = $model->aggregator_id;
                    $rel->ownership_type_id = $ownershipId;
                    $rel->save(false);
                }
            }
            
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
            $model->load(Yii::$app->request->post());
            $model->file = UploadedFile::getInstance($model, 'file');

            if (!$model->file) {
                throw new RuntimeException('Please Select CSV File');
            }

            try {
                $path = Yii::getAlias('@backend/uploads/');
                $extension = strtolower((string)$model->file->extension);

                if (!is_dir($path)) {
                    if (!mkdir($path, 0777, true) && !is_dir($path)) {
                        throw new RuntimeException('Не вдалося створити папку для завантажених файлів.');
                    }
                }

                if (!is_writable($path)) {
                    throw new RuntimeException('Папка для завантажених файлів не доступна для запису.');
                }

                $filePath = $path . uniqid('upload_') . '.' . $extension;

                if (!$model->file->saveAs($filePath)) {
                    throw new RuntimeException('Failed to save the uploaded file.');
                }

                $file_header = [];
                $importResults = [];

                if ($extension === 'csv') {
                    if (($handle = fopen($filePath, 'r')) !== false) {
                        $file_header = fgetcsv($handle) ?: [];
                        $i = 0;
                        while (($row = fgetcsv($handle)) !== false) {
                            $importResults[] = $row;
                            $i++;
                            if ($i === 5) {
                                break;
                            }
                        }

                        fclose($handle);
                    }
                } else {
                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
                    $reader->setReadDataOnly(true);
                    $spreadsheet = $reader->load($filePath);
                    $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

                    $file_header = array_values($rows[0] ?? []);
                    foreach (array_slice($rows, 1, 10) as $row) {
                        $importResults[] = array_values($row);
                    }
                }

                $s = Yii::$app->cache->set('file_meta', [
                    'path' => $filePath,
                    'file_extension' => $extension,
                    'aggregator_id' => $model->aggregatorId,
                    'quarter' => $model->quarter,
                    'year' => $model->year,
                ], 600);

                if (!$s) {
                    throw new RuntimeException('Failed to save file metadata to cache.');
                }

            } catch (\Throwable $e) {
                throw new RuntimeException($e->getMessage());
            }

            if (empty($importResults)) {
                throw new RuntimeException('Failed to read the uploaded file.');
            }

            return $this->renderAjax('temp-upload', [
                'count_header' => count($file_header),
                'file_header' => $file_header,
                'file_data' => $importResults,
                'aggregator_id' => (int)$model->aggregatorId,
            ]);
        }

        return $this->render('upload', ['model' => $model]);
    }

    public function actionUploadImport()
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(0);

        if (!Yii::$app->request->isPost) {
            return $this->asJson(['message' => 'Invalid request']);
        }

        $cache = Yii::$app->cache->get('file_meta');

        if (!$cache || empty($cache['path']) || !file_exists($cache['path'])) {
            return $this->asJson(['message' => 'Файл не знайдено']);
        }

        $path = $cache['path'];

        $columns = Yii::$app->request->post('columns', []);
        if (!is_array($columns)) {
            $columns = [];
        }

        $columns = array_filter($columns, static fn($value) => $value !== '' && $value !== null);
        $columns = array_map('intval', $columns);

        if (!isset($columns['isrc'], $columns['date_report'], $columns['country'], $columns['platform'], $columns['count'], $columns['amount'])) {
            return $this->asJson([
                'success' => false,
                'message' => 'Потрібно обрати всі поля: Країна, ISRC, Місяць звіту, Платформа, Кількість переглядів, Сума.',
            ]);
        }

        $readColumn = static function (array $row, string $field, $default = '') use ($columns) {
            if (!array_key_exists($field, $columns)) {
                return $default;
            }

            $index = $columns[$field];

            return $row[$index] ?? $default;
        };

        $parseDate = static function ($value): string {
            if ($value === null || $value === '') {
                return date('Y-m-d');
            }

            if (is_numeric($value)) {
                try {
                    return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value)->format('Y-m-d');
                } catch (\Throwable $e) {
                    return date('Y-m-d');
                }
            }

            $timestamp = strtotime((string)$value);

            return $timestamp ? date('Y-m-d', $timestamp) : date('Y-m-d');
        };

        $parseNumber = static function ($value, bool $asInt = false) {
            if ($value === null || $value === '') {
                return $asInt ? 0 : 0.0;
            }

            $normalized = str_replace([' ', ','], ['', '.'], (string)$value);
            $number = (float)$normalized;

            return $asInt ? (int)$number : $number;
        };

        $tx = Yii::$app->db->beginTransaction();

        try {
            $modelReport = new AggregatorReport();
            $modelReport->load([
                'AggregatorReport' => [
                    'aggregator_id' => $cache['aggregator_id'],
                    'quarter' => $cache['quarter'],
                    'year' => $cache['year'],
                    'user_id' => Yii::$app->user->id,
                    'total' => 0
                ]
            ]);

            $modelReport->save();

            $total = 0;
            $map = [];
            $mapLimit = 5000;
            $criticalWarnings = [];
            $nonCriticalWarnings = [];
            $warningSeen = ['critical' => [], 'non_critical' => []];

            $addWarning = static function (array &$target, array &$seen, string $message) {
                $clean = trim($message);
                if ($clean === '') {
                    return;
                }

                if (isset($seen[$clean])) {
                    return;
                }

                $seen[$clean] = true;
                $target[] = $clean;
            };

            $rawIsrcForWarning = static function ($value): string {
                $raw = trim((string)($value ?? ''));
                return preg_replace('/\s+/', ' ', $raw);
            };

            $warningFromIsrc = static function ($value, string $prefix): string {
                $raw = trim((string)($value ?? ''));
                if ($raw === '') {
                    return $prefix . ' (порожнє значення)';
                }

                return $prefix . ' (' . preg_replace('/\s+/', ' ', $raw) . ')';
            };

            $hasValidIsrc = static function ($value): bool {
                $raw = trim((string)($value ?? ''));
                if ($raw === '') {
                    return false;
                }

                return preg_match('/^[A-Z0-9]{12}$/i', preg_replace('/[^A-Z0-9]/i', '', $raw)) === 1;
            };

            $resolveWarnings = function () use ($modelReport, $addWarning, &$criticalWarnings, &$nonCriticalWarnings, &$warningSeen, $warningFromIsrc, $rawIsrcForWarning, $hasValidIsrc) {
                $critical = $this->getMissingTrackWarnings((int)$modelReport->id);
                foreach ($critical as $item) {
                    $addWarning($criticalWarnings, $warningSeen['critical'], $item);
                }

                $nonCritical = [];
                $rows = Yii::$app->db->createCommand('SELECT DISTINCT isrc FROM aggregator_report_item WHERE report_id = :reportId AND isrc IS NOT NULL AND isrc <> :empty')
                    ->bindValue(':reportId', $modelReport->id)
                    ->bindValue(':empty', '')
                    ->queryColumn();

                foreach ($rows as $raw) {
                    $value = $rawIsrcForWarning($raw);
                    if ($value === '') {
                        $nonCritical[] = $warningFromIsrc($value, 'Порожній ISRC');
                        continue;
                    }

                    if (!$hasValidIsrc($value)) {
                        $nonCritical[] = $warningFromIsrc($value, 'Некоректний ISRC');
                        continue;
                    }
                }

                foreach ($nonCritical as $item) {
                    $addWarning($nonCriticalWarnings, $warningSeen['non_critical'], $item);
                }
            };

            $flushMap = function () use (&$map, $modelReport) {
                if (empty($map)) {
                    return;
                }

                $rows = array_values($map);
                $this->saveBatch($rows);
                $map = [];
                gc_collect_cycles();
            };

            $foundBroma = [];
            if ($cache['aggregator_id'] == 2) {
                $foundBroma = (new \yii\db\Query())
                    ->select(['isrc', 'number'])
                    ->from('broma')
                    ->indexBy('number')
                    ->column();
            }

            $invalidIsrcRows = [];

            $buildInvalidRowPayload = static function (int $rowNumber, string $rawValue, string $issue, ?string $symbol = null): array {
                return [
                    'row' => $rowNumber,
                    'value' => $rawValue,
                    'issue' => $issue,
                    'symbol' => $symbol,
                ];
            };

            $detectInvalidIsrcIssue = function (string $value): array {
                $trimmed = trim($value);
                if ($trimmed === '') {
                    return [
                        'issue' => 'Порожнє значення',
                        'symbol' => null,
                    ];
                }

                $clean = strtoupper($trimmed);
                $invalidChars = preg_replace('/[A-Z0-9\s-]/', '', $clean);
                if ($invalidChars !== '') {
                    $firstInvalid = mb_substr($invalidChars, 0, 1, 'UTF-8');
                    return [
                        'issue' => 'Зайвий або неправильний символ',
                        'symbol' => $firstInvalid,
                    ];
                }

                if (!preg_match('/^(?:ISRC[\s-]*)?[A-Z]{2}[\s-]*[A-Z0-9]{3}[\s-]*\d{2}[\s-]*\d{5}[\s-]*$/', $clean)) {
                    $candidate = preg_replace('/[^A-Z0-9]/', '', $clean);
                    $missing = strlen($candidate) !== 12 ? 'Неправильна довжина ISRC' : 'Невірна структура ISRC';
                    return [
                        'issue' => $missing,
                        'symbol' => null,
                    ];
                }

                return [
                    'issue' => 'Не пройшов валідацію ISRC',
                    'symbol' => null,
                ];
            };

            $normalizeRecord = function (array $row, int $rowNumber = 0) use ($cache, $readColumn, $parseDate, $parseNumber, $foundBroma, $buildInvalidRowPayload, $detectInvalidIsrcIssue) {
                $rawIsrc = trim((string) $readColumn($row, 'isrc'));
                if ($rawIsrc === '') {
                    throw new RuntimeException(json_encode($buildInvalidRowPayload(
                        $rowNumber,
                        $rawIsrc,
                        'Порожнє значення',
                        null
                    ), JSON_THROW_ON_ERROR));
                }

                $normalizedRawIsrc = strtoupper($rawIsrc);
                $isrcPattern = '/^(?:ISRC[\s-]*)?[A-Z]{2}[\s-]*[A-Z0-9]{3}[\s-]*\d{2}[\s-]*\d{5}[\s-]*$/';
                if (!preg_match($isrcPattern, $normalizedRawIsrc)) {
                    $issue = $detectInvalidIsrcIssue($rawIsrc);
                    throw new RuntimeException(json_encode($buildInvalidRowPayload(
                        $rowNumber,
                        $rawIsrc,
                        $issue['issue'],
                        $issue['symbol']
                    ), JSON_THROW_ON_ERROR));
                }

                $cleanIsrc = preg_replace('/[^A-Z0-9]/i', '', $rawIsrc);
                if ($cleanIsrc === '') {
                    $issue = $detectInvalidIsrcIssue($rawIsrc);
                    throw new RuntimeException(json_encode($buildInvalidRowPayload(
                        $rowNumber,
                        $rawIsrc,
                        $issue['issue'],
                        $issue['symbol']
                    ), JSON_THROW_ON_ERROR));
                }

                if ($cache['aggregator_id'] == 2) {
                    $cleanIsrc = $foundBroma[$cleanIsrc] ?? $cleanIsrc;
                }

                $isrcObj = new Isrc($cleanIsrc);
                if (!$isrcObj->isValid()) {
                    $issue = $detectInvalidIsrcIssue($rawIsrc);
                    throw new RuntimeException(json_encode($buildInvalidRowPayload(
                        $rowNumber,
                        $rawIsrc,
                        $issue['issue'],
                        $issue['symbol']
                    ), JSON_THROW_ON_ERROR));
                }

                $isr = $isrcObj->getIsrc(false);
                if ($isr === '') {
                    $issue = $detectInvalidIsrcIssue($rawIsrc);
                    throw new RuntimeException(json_encode($buildInvalidRowPayload(
                        $rowNumber,
                        $rawIsrc,
                        $issue['issue'],
                        $issue['symbol']
                    ), JSON_THROW_ON_ERROR));
                }

                $country = trim((string)$readColumn($row, 'country', ''));
                $platform = trim((string)$readColumn($row, 'platform', '')) ?: 'Загальний';
                $date = $parseDate($readColumn($row, 'date_report'));
                $count = $parseNumber($readColumn($row, 'count', 0), true);
                $amount = $parseNumber($readColumn($row, 'amount', 0));

                return [
                    'country' => $country,
                    'platform' => $platform,
                    'date_report' => $date,
                    'count' => $count,
                    'amount' => $amount,
                    'isrc' => $isr,
                ];
            };

            $appendNormalizedRow = function (array $row, int $rowNumber = 0) use (&$map, &$total, $modelReport, $normalizeRecord, &$invalidIsrcRows) {
                try {
                    $normalized = $normalizeRecord($row, $rowNumber);
                } catch (RuntimeException $e) {
                    $decoded = json_decode($e->getMessage(), true);
                    if (is_array($decoded) && array_key_exists('row', $decoded)) {
                        $invalidIsrcRows[] = $decoded;
                    } else {
                        $invalidIsrcRows[] = [
                            'row' => $rowNumber,
                            'value' => trim((string) $readColumn($row, 'isrc')),
                            'issue' => $e->getMessage(),
                            'symbol' => null,
                        ];
                    }
                    return;
                }

                if ($normalized === null) {
                    return;
                }

                $key = $normalized['date_report'] . '|' . $normalized['country'] . '|' . $normalized['platform'] . '|' . $normalized['isrc'];

                if (!isset($map[$key])) {
                    $map[$key] = [
                        'report_id' => $modelReport->id,
                        'isrc' => $normalized['isrc'],
                        'platform' => $normalized['platform'],
                        'date_report' => $normalized['date_report'],
                        'country' => $normalized['country'],
                        'count' => 0,
                        'amount' => 0,
                    ];
                }

                $map[$key]['count'] += $normalized['count'];
                $map[$key]['amount'] += $normalized['amount'];
                $total += $normalized['amount'];
            };

            // Broma cache
            if ($cache['aggregator_id'] == 2) {
                $foundBroma = (new \yii\db\Query())
                    ->select(['isrc', 'number'])
                    ->from('broma')
                    ->indexBy('number')
                    ->column();
            }

            if ($cache['file_extension'] === 'csv') {
                $handle = fopen($path, 'r');
                if ($handle === false) {
                    throw new RuntimeException('Не вдалося відкрити CSV файл для імпорту.');
                }

                $header = fgetcsv($handle);
                if ($header === false) {
                    fclose($handle);
                    throw new RuntimeException('Файл CSV порожній або пошкоджений.');
                }

                $lineNumber = 2;
                while (($row = fgetcsv($handle)) !== false) {
                    $appendNormalizedRow($row, $lineNumber);
                    $lineNumber++;
                    if (count($map) >= $mapLimit) {
                        $flushMap();
                    }
                }

                fclose($handle);
            } else {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($path);
                $worksheet = $spreadsheet->getActiveSheet();

                foreach ($worksheet->getRowIterator() as $rowIterator) {
                    $rowIndex = $rowIterator->getRowIndex();
                    if ($rowIndex === 1) {
                        continue;
                    }

                    $cellIterator = $rowIterator->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    $values = [];
                    foreach ($cellIterator as $cell) {
                        $values[] = $cell->getValue();
                    }

                    $appendNormalizedRow(array_values($values), $rowIndex);
                    if (count($map) >= $mapLimit) {
                        $flushMap();
                    }
                }

                unset($worksheet, $spreadsheet);
                gc_collect_cycles();
            }

            if (!empty($invalidIsrcRows)) {
                $uniqueInvalidIsrcRows = [];
                $seen = [];

                foreach ($invalidIsrcRows as $item) {
                    if (!is_array($item)) {
                        continue;
                    }

                    $row = (int)($item['row'] ?? 0);
                    $value = (string)($item['value'] ?? '');
                    $issue = (string)($item['issue'] ?? 'Невідоме поле ISRC');
                    $symbol = array_key_exists('symbol', $item) && $item['symbol'] !== null ? (string)$item['symbol'] : null;
                    $key = $row . '|' . $value . '|' . $issue . '|' . ($symbol ?? '');

                    if (isset($seen[$key])) {
                        continue;
                    }

                    $seen[$key] = true;
                    $uniqueInvalidIsrcRows[] = [
                        'row' => $row,
                        'value' => $value,
                        'issue' => $issue,
                        'symbol' => $symbol,
                    ];
                }

                throw new RuntimeException(json_encode([
                    'success' => false,
                    'message' => 'Виявлено невалідні ISRC',
                    'invalid_rows' => $uniqueInvalidIsrcRows,
                ], JSON_THROW_ON_ERROR));
            }

            $flushMap();

            Yii::$app->db->createCommand("
                UPDATE aggregator_report_item a
                JOIN track t ON t.isrc = a.isrc
                SET a.track_id = t.id
                WHERE a.track_id IS NULL
                AND a.report_id = {$modelReport->id}
            ")->execute();

            $modelReport->total = round($total, 4);
            $modelReport->save(false);

            // Класифікація помилок
            $resolveWarnings();

            $tx->commit();

            unlink($path);
            Yii::$app->cache->delete('file_meta');

            return $this->asJson([
                'success' => true,
                'message' => 'Імпорт завершено успішно.',
                'warning_message' => empty($criticalWarnings) ? '' : 'Не знайдено ' . count($criticalWarnings) . ' трек(ів) у системі.',
                'warnings' => array_merge($criticalWarnings, $nonCriticalWarnings),
                'critical_warnings' => $criticalWarnings,
                'non_critical_warnings' => $nonCriticalWarnings,
                'missing_tracks' => $criticalWarnings,
                'invalid_rows' => [],
                'redirect_url' => Yii::$app->urlManager->createUrl(['/aggregator-report/view', 'id' => $modelReport->id]),
            ]);

        } catch (\Throwable $e) {
            $tx->rollBack();
            @unlink($path);
            Yii::$app->cache->delete('file_meta');

            $payload = [
                'success' => false,
                'message' => $e->getMessage(),
                'invalid_rows' => [],
            ];

            $decoded = json_decode($e->getMessage(), true);
            if (is_array($decoded) && array_key_exists('invalid_rows', $decoded)) {
                $payload = array_merge($payload, $decoded);
            }

            return $this->asJson($payload);
        }
    }

    private function saveBatch(array $rows)
    {
        if (empty($rows)) {
            return;
        }

        Yii::$app->db->createCommand()
            ->batchInsert(
                AggregatorReportItem::tableName(),
                array_keys($rows[0]),
                $rows
            )->execute();
    }


    /**
     * Завантаження звіту в БД
     *
     * @return void|Response
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionUploadImport1()
    {
        if (Yii::$app->request->isPost) {
            $cache = Yii::$app->cache->get('file_data');
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
            
            if ($cache['aggregator_id'] == 2) { // Broma
                $foundBroma = (new Query())
                    ->select(['isrc', 'number'])
                    ->from('broma')
                    ->indexBy('number')
                    ->column();
            }
            
            foreach ($file_data as $row) {
                $isr = preg_replace('/[^a-zA-Z0-9]/u', '', $row[$isrc]);

                if ($cache['aggregator_id'] == 2) { // Broma
                    $isr = $foundBroma[$isr] ?? $isr;
                }

                $isrcObject = new Isrc($isr);
                $isr = $isrcObject->getIsrc(false);
                
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

            if (!empty($data)) {
                $tx = Yii::$app->db->beginTransaction();
                
                try {
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
                    $data2 = [];
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
                                        
                                        $c += (int)$item['count'];
                                        $a += round($item['amount'], 4);
                                    }
                                    
                                    $temp_value['count'] = $c;
                                    $temp_value['amount'] = $a;
                                    
                                    $data2[] = $temp_value;
                                }
                            }
                        }
                    }
                    
                    if (!empty($data2)) {
                        Yii::$app->db->createCommand()
                            ->batchInsert(
                                AggregatorReportItem::tableName(),
                                array_keys(current($data2)),
                                $data2
                            )->execute();
                        
                        Yii::$app->db->createCommand(
                            "UPDATE aggregator_report_item a
					            JOIN track t ON t.isrc = a.isrc
				            SET a.`track_id` = t.id
				                WHERE a.track_id is null
				                    and t.isrc is not null
				                    and a.report_id = {$modelReport->id}"
                        )->execute();
                        $tx->commit();
                        
                        return $this->redirect(['/aggregator-report/view', 'id' => $modelReport->id]);
                    } else {
                        $tx->rollBack();
                        echo json_encode([
                            'message' => 'Помилка завантаже��ня',
                        ]);
                        die;
                    }
                } catch (Throwable $e) {
                    $tx->rollBack();
                    
                    echo json_encode([
                        'message' => 'Помилка збереження даних звіту: ' . $e->getMessage(),
                    ]);
                    
                    die;
                }
            }
            
            echo json_encode([
                'message' => 'Помилка завантаження',
            ]);
            die;
            
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
       // ini_set('memory_limit', '2048M');
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
                        fgetcsv($file_data);

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
                }

                if (empty($importResults)) {
                    Yii::$app->session->setFlash('error', 'Не вдалось прочитати файл');

                    return $this->render('import',
                        [
                            'model' => $model,
                            'result' => $importResults,
                        ]);
                }

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

                            Yii::$app->db->createCommand($sql, $parameters)->execute();
                        }
                    }

                    $i++;
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

    private function getMissingTrackWarnings(int $reportId): array
    {
        $rows = AggregatorReportItem::find()
            ->select(['isrc'])
            ->where(['report_id' => $reportId, 'track_id' => null])
            ->andWhere(['not', ['isrc' => null]])
            ->andWhere(['<>', 'isrc', ''])
            ->groupBy(['isrc'])
            ->asArray()
            ->all();

        $warnings = [];

        foreach ($rows as $row) {
            $isrc = trim((string)($row['isrc'] ?? ''));
            if ($isrc === '') {
                continue;
            }

            $warnings[] = (new \backend\helpers\Isrc($isrc))->getIsrc(true, true) . ' (' . $isrc . ')';
        }

        return array_values(array_unique($warnings));
    }
    #endregion
}
