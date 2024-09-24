<?php

namespace backend\models;

use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

/**
 * @property int $aggregatorId
 */
class UploadReport extends Model
{
    /**
     * @var ?UploadedFile
     */
    public ?UploadedFile $file = null;
    public ?int $aggregatorId = null;

    public function rules(): array
    {
        return [
            [['aggregatorId'], 'required'],
            [['aggregatorId'], 'integer'],
            [['file'], 'file',
                'extensions'=>'csv',
                //'wrongType' => 'Дозволяється тільки csv.',
                'maxSize' => 1024 * 1024 * 100, // 100MB
                //'tooLarge' => 'Розмір файлу перевищує 10 МБ. Будь ласка, завантажте файл меншого розміру.',
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
            'file' => Yii::t('app', 'Звіт'),
        ];
    }

    public function upload(): bool
    {
        if ($this->validate()) {
            $this->file->saveAs('uploads/' . $this->file->baseName . '.' . $this->file->extension);
            return true;
        } else {
            return false;
        }
    }
}