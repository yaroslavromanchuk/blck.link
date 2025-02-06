<?php
namespace backend\models;

use Yii;
use yii\base\Model;
use common\models\User;
use common\models\SubLabel;

/**
 * Signup form
 */
class SignupLabelForm extends Model
{
    public string $username;
    public string $email;
    public string $password;
    public string $url;
    public int $label_id;

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            ['username', 'trim'],
            ['username', 'required'],
            ['username', 'unique', 'targetClass' => '\common\models\User', 'message' => 'Це ім\'я користувача вже зайнято.'],
            ['username', 'string', 'min' => 2, 'max' => 255],

            ['url', 'trim'],
            ['url', 'required'],
            ['url', 'unique', 'targetClass' => SubLabel::class, 'message' => 'Цей URL вже використовується іншим лейблом.'],
            ['url', 'string', 'min' => 2, 'max' => 50],

            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetClass' => '\common\models\User', 'message' => 'This email address has already been taken.'],

            ['password', 'required'],
            ['password', 'string', 'min' => 6, 'max' => 16],

            ['label_id', 'trim'],
            ['label_id', 'required'],
            ['label_id', 'integer'],
            ['label_id', 'exist', 'skipOnError' => true, 'targetClass' => SubLabel::class, 'targetAttribute' => ['label_id' => 'id']],
        ];
    }

    /**
     * Signs user up.
     *
     * @return bool whether the creating new account was successful and email was sent
     * @throws \Exception
     */
    public function signup()
    {
        if (!$this->validate()) {
            return false;
        }
        
        $user = new User();
        $user->username = $this->username;
        $user->email = $this->email;
        $user->label_id = $this->label_id;// sub-label
        $user->status = User::STATUS_PENDING_APPROVAL;
        $user->setPassword($this->password);

        $user->generateAuthKey();
        $user->generateEmailVerificationToken();

        $user->role = User::ROLE_LABEL;

        if (!$user->save()) {
            return false;
        }

        $user->addRole();
        //$this->sendEmail($user);

        return $user;
    }

    /**
     * Sends confirmation email to user
     * @param User $user user model to with email should be send
     * @return bool whether the email was sent
     */
    protected function sendEmail($user)
    {
        return Yii::$app
            ->mailer
            ->compose(
                ['html' => 'emailVerify-html', 'text' => 'emailVerify-text'],
                ['user' => $user]
            )
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name . ' robot'])
            ->setTo($this->email)
            ->setSubject('Account registration at ' . Yii::$app->name)
            ->send();
    }
}
