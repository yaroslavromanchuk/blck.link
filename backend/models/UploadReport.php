<?php

namespace backend\models;

use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

/**
 * @property UploadedFile $file
 * @property int $aggregatorId
 * @property int $quarter
 * @property int $year
 */
class UploadReport extends Model
{
    /**
     * @var string|UploadedFile
     */
    public string|UploadedFile $file = '';
    public ?int $aggregatorId = null;
    public ?int $quarter = null;
    public ?int $year = null;

    public function rules(): array
    {
        return [
            [['aggregatorId', 'quarter', 'year'], 'required'],
            [['aggregatorId', 'quarter', 'year'], 'integer'],
            [['file'], 'file',
                'extensions'=>'xls,xlsx,csv',
                //'wrongType' => 'Дозволяється тільки csv.',
                'maxSize' => 1024 * 1024 * 30, // 15MB
                //'tooLarge' => 'Розмір файлу перевищує 15 МБ. Будь ласка, завантажте файл меншого розміру.',
                'skipOnEmpty' => false,
            ],

        ];
    }
    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'aggregatorId' => Yii::t('app', 'Агрегатор'),
            'quarter' => Yii::t('app', 'Квартал'),
            'year' => Yii::t('app', 'Рік'),
            'file' => Yii::t('app', 'Звіт'),
        ];
    }

    public function upload(): bool
    {
        if ($this->validate('file')) {
            $this->file->saveAs('uploads/' . $this->file->baseName . '.' . $this->file->extension);

            return true;
        } else {
            return false;
        }
    }
}