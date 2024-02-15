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
    public $username;
    public $email;
    public $password;
    public $url;

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
            ['url', 'unique', 'targetClass' => '\common\models\SubLabel', 'message' => 'Цей URL вже використовується іншим лейблом.'],
            ['url', 'string', 'min' => 2, 'max' => 50],

            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetClass' => '\common\models\User', 'message' => 'This email address has already been taken.'],

            ['password', 'required'],
            ['password', 'string', 'min' => 6, 'max' => 16],
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
        $user->type = 2;// sub-label
        $user->status = User::STATUS_ACTIVE;
        $user->setPassword($this->password);

        $user->generateAuthKey();
        $user->generateEmailVerificationToken();

        $user->role = User::ROLE_LABEL;

        if (!$user->save()) {
            return false;
        }

        $user->addRole();
        $this->addSubLabel($user->id, $this->url);
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

    protected function addSubLabel(int $userId, string $url)
    {
        $subLabel = new SubLabel();
        $subLabel->user_id = $userId;
        $subLabel->url = $url;

        $subLabel->save();
    }
}
