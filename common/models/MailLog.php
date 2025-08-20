<?php

namespace common\models;


/**
 * This is the model class for table "mail_log".
 *
 * @property int $log_id
 * @property int $artist_id
 * @property int $track_id
 * @property int $invoice_id
 * @property string $content
 * @property int $user_id
 * @property string $date_added
 * @property string $last_update
 */
class MailLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mail_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['artist_id'], 'required'],
            [['artist_id', 'track_id', 'invoice_id', 'user_id'], 'integer'],
            [['content'], 'string'],
            [['date_added', 'last_update'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'log_id' => 'Log ID',
            'artist_id' => 'Artist ID',
            'track_id' => 'Track ID',
            'invoice_id' => 'Invoice ID',
            'content' => 'Content',
            'date_added' => 'Date Added',
            'last_update' => 'Last Update',
        ];
    }

}
