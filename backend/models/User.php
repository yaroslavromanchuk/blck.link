<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "user".
 *
 * @property int $id
 * @property string $username
 * @property string $email
 * @property string $lastName
 * @property string $firstName
 * @property string $middleName
 * @property string $sex
 * @property string $logo
 * @property string $auth_key
 * @property string $password_hash
 * @property string $password_reset_token
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 */
class User extends \yii\db\ActiveRecord
{
    public $file;
    public $pass; 
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
             [['file'], 'file', 'extensions' => 'png, jpg'],
            [['username', 'email', 'auth_key', 'password_hash', 'created_at', 'updated_at'], 'required'],
            [['status', 'created_at', 'updated_at'], 'integer'],
            [['username', 'email', 'logo', 'password_hash', 'password_reset_token'], 'string', 'max' => 255],
            [['lastName', 'firstName', 'middleName'], 'string', 'max' => 100],
            [['sex'], 'string', 'max' => 10],
            [['auth_key', 'pass'], 'string', 'max' => 32],
            [['username'], 'unique'],
            [['email'], 'unique'],
            [['password_reset_token'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
	{
        return [
            'id' => 'Id',
            'username' => Yii::t('app', 'Логин'),
            'email' => 'Email',
            'lastName' => Yii::t('app', 'Фамилия'),
            'firstName' => Yii::t('app', 'Имя'),
            'middleName' => Yii::t('app', 'Отчество'),
            'sex' => Yii::t('app', 'Пол'),
            'logo' => Yii::t('app', 'Иконка'),
            'auth_key' => 'Auth Key',
            'password_hash' => 'Password Hash',
            'password_reset_token' => 'Password Reset Token',
            'status' => Yii::t('app', 'Статус'),
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
     public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * {@inheritdoc}
     * @return UserQuery the active query used by this AR class.
     */
    public static function find(): UserQuery
	{
        return new UserQuery(get_called_class());
    }

    public function getImg(): string
    {
        if($this->logo) {
            return Yii::getAlias('@site').'/images/user/'.$this->logo;
        }

        return false;
    }
     public function getRole()
    {
        return array_values(Yii::$app->authManager->getRolesByUser($this->id))[0]->name;
    }

    public function getFullName(): string
	{
        return $this->lastName.' '.$this->firstName;
    }
}
