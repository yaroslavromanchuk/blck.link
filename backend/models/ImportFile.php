<?php

namespace backend\models;

use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

class ImportFile extends Model
{
    /**
     * @var string|UploadedFile
     */
    public string|UploadedFile $file = '';
    public int $isBroma = 0;
    public function rules(): array
    {
        return [
            [['file'], 'required'],
            ['isBroma', 'integer'],
            [['file'], 'file',
                'extensions'=>'xls,xlsx,csv',
                //'wrongType' => 'Дозволяється тільки csv.',
                'maxSize' => 1024 * 1024 * 15, // 15MB
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
            'file' => Yii::t('app', 'Файл'),
            'isBroma' => Yii::t('app', 'Звіт Broma'),
        ];
    }

    public function upload(): bool
    {
        if ($this->validate('file')) {
            return $this->file->saveAs('uploads/' . $this->file->baseName . '.' . $this->file->extension);
        } else {
            return false;
        }
    }
}