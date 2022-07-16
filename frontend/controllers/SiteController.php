<?php
namespace frontend\controllers;

use frontend\models\ResendVerificationEmailForm;
use frontend\models\VerifyEmailForm;
use Yii;
use yii\base\InvalidArgumentException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;
use frontend\models\ContactForm;
use frontend\models\Track;

use frontend\models\Sitemap;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
      

       if (empty($_SESSION["referal"])){ $_SESSION["referal"] = $_SERVER["HTTP_REFERER"];}

        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        
        $this->view->registerMetaTag(['name' => 'description', 'content' => 'Listen, download or stream', 'data-hid'=>'description'],'description');
        $this->view->registerMetaTag(['property' => 'og:url', 'content' => ''], 'og:url');
        $this->view->registerMetaTag(['property'=>'og:title', 'content' => ' | BlckLink'], 'og:title');
        $this->view->registerMetaTag(['property'=>'og:description', 'content' => 'Listen, download or stream'], 'og:description');
        $this->view->registerMetaTag(['property' => 'og:image:width', 'content' => '200'],'og:image:width');
        $this->view->registerMetaTag(['property' => 'og:image:height', 'content' => '200'],'og:image:height');
        $this->view->registerMetaTag(['property' => 'og:image', 'content' => '/img/logo.png'],'og:image');
        return $this->render('index',[
            'list' => Track::find()
				->andFilterWhere(['active'=>1])
				->andFilterWhere(['<=', '`date`',date('Y-m-d')])
				->orderBy('date DESC')
				->limit(100)
				->all()
        ]);
    }
    
     public function actionView()
    { 
         $track = Track::find()
                ->andFilterWhere(['like', 'url', Yii::$app->request->get('link')])
                ->andFilterWhere(['active'=>1])
                ->andFilterWhere(['<=', '`date`', date('Y-m-d')])
                ->one();
         if(!$track){
             return $this->redirect(['index']);
         }
          $track->views = (int)($track->views+1);
          $track->save();
                    $view = new \common\models\Views();
                    $view->track_id = $track->id;
                    $view->view = 1;
                    $view->ip = Yii::$app->request->userIP;
                    $country = geoip_country_name_by_name(Yii::$app->request->userIP);
                    $view->country = $country ? $country : null; 
                    $view->referal = !empty($_SESSION["referal"]) ? $_SESSION["referal"] : $_SERVER['HTTP_REFERER'];    
                    $view->data = date("Y-m-d");
                    $view->save();
          
        $this->view->registerMetaTag(['name' => 'description', 'content' => 'Listen, download or stream '.$track->name.'!', 'data-hid'=>'description'],'description');
        $this->view->registerMetaTag(['property' => 'og:url', 'content' => '/'.$track->url], 'og:url');
        $this->view->registerMetaTag(['property'=>'og:title', 'content' => $track->artist.' - '.$track->name.' | BlckLink'], 'og:title');
        $this->view->registerMetaTag(['property'=>'og:description', 'content' => 'Listen, download or stream '.$track->name.'!'], 'og:description');
        $this->view->registerMetaTag(['property' => 'og:image:width', 'content' => '200'],'og:image:width');
        $this->view->registerMetaTag(['property' => 'og:image:height', 'content' => '200'],'og:image:height');
        $this->view->registerMetaTag(['property' => 'og:image', 'content' => $track->getImage()],'og:image');
        
        $this->view->registerMetaTag(['name' => 'twitter:card', 'content' => 'summary_large_image'],'twitter:card');
        $this->view->registerMetaTag(['name' => 'twitter:site', 'content' => '@'.$track->url],'twitter:site');
        $this->view->registerMetaTag(['name' => 'twitter:title', 'content' => $track->artist.' - '.$track->name.' | BlckLink'],'twitter:title');
        $this->view->registerMetaTag(['name' => 'twitter:description', 'content' => 'Listen, download or stream '.$track->name.'!'],'twitter:description');
        $this->view->registerMetaTag(['name' => 'twitter:image', 'content' =>  $track->getImage()],'twitter:image');

        return $this->render('view', [
            'track' => $track,
        ]);
    }
    public function actionAjax()
    {
        if(Yii::$app->request->isAjax){
           // print_r(Yii::$app->request);
            if(Yii::$app->request->post('method') == 'servise' || Yii::$app->request->post('method') == 'link'){
                $id = (int)Yii::$app->request->post('id');
                $name = Yii::$app->request->post('name');
            
            if (($track = Track::findOne($id))){
                    $track->click = (int)($track->click+1);
                    $track->save();
                    
                    $log = new \common\models\Log();
                    $log->track = $track->id;
                    $log->type = Yii::$app->request->post('method');
                    $log->name = Yii::$app->request->post('name');
                    $log->referal = !empty($_SESSION["referal"]) ? $_SESSION["referal"] : $_SERVER['HTTP_REFERER'];
                    $log->ip = Yii::$app->request->userIP;
                        $country = geoip_country_name_by_name(Yii::$app->request->userIP);
                    $log->country = $country ? $country : null; 
                    $log->data = date("Y-m-d");
                    $log->save();
                 return true;
            }
        }
        }
        return false;
    }

    /**
     * Logs in a user.
     *
     * @return mixed
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            $model->password = '';

            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return mixed
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail(Yii::$app->params['adminEmail'])) {
                Yii::$app->session->setFlash('success', 'Thank you for contacting us. We will respond to you as soon as possible.');
            } else {
                Yii::$app->session->setFlash('error', 'There was an error sending your message.');
            }

            return $this->refresh();
        } else {
            return $this->render('contact', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Displays about page.
     *
     * @return mixed
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post()) && $model->signup()) {
            Yii::$app->session->setFlash('success', 'Thank you for registration. Please check your inbox for verification email.');
            return $this->goHome();
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    /**
     * Requests password reset.
     *
     * @return mixed
     */
    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');

                return $this->goHome();
            } else {
                Yii::$app->session->setFlash('error', 'Sorry, we are unable to reset password for the provided email address.');
            }
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', 'New password saved.');

            return $this->goHome();
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }

    /**
     * Verify email address
     *
     * @param string $token
     * @throws BadRequestHttpException
     * @return yii\web\Response
     */
    public function actionVerifyEmail($token)
    {
        try {
            $model = new VerifyEmailForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        if ($user = $model->verifyEmail()) {
            if (Yii::$app->user->login($user)) {
                Yii::$app->session->setFlash('success', 'Your email has been confirmed!');
                return $this->goHome();
            }
        }

        Yii::$app->session->setFlash('error', 'Sorry, we are unable to verify your account with provided token.');
        return $this->goHome();
    }

    /**
     * Resend verification email
     *
     * @return mixed
     */
    public function actionResendVerificationEmail()
    {
        $model = new ResendVerificationEmailForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');
                return $this->goHome();
            }
            Yii::$app->session->setFlash('error', 'Sorry, we are unable to resend verification email for the provided email address.');
        }

        return $this->render('resendVerificationEmail', [
            'model' => $model
        ]);
    }
     //Карта сайта. Выводит в виде XML файла.
    public function actionSitemap(){
        
    $sitemap = new Sitemap();
    
   // $urls = $sitemap->getUrl();
        //Формируем XML файл
     // $xml_sitemap = $sitemap->getXml($urls);
    //Если в кэше нет карты сайта        
    if (!$xml_sitemap = Yii::$app->cache->get('sitemap_'.Yii::$app->language)){
        //Получаем мыссив всех ссылок
        $urls = $sitemap->getUrl();
        //Формируем XML файл
        $xml_sitemap = $sitemap->getXml($urls);
        // кэшируем результат
        Yii::$app->cache->set('sitemap_'.Yii::$app->language, $xml_sitemap, 3600*12); 
    } 
    return $this->render('sitemap', [
            'model' => $xml_sitemap,
        ]);
}
}
