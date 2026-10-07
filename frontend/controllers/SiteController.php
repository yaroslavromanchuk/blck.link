<?php
namespace frontend\controllers;

use aki\telegram\Telegram;
use backend\models\User;
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
use frontend\models\Artist;

use aki\telegram\base\Response;
use aki\telegram\base\TelegramBase;
use aki\telegram\base\Command;
use aki\telegram\base\Input;

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
       if (empty($_SESSION["referal"]) && isset($_SERVER["HTTP_REFERER"])) {
       		$_SESSION["referal"] = $_SERVER["HTTP_REFERER"];
       }

        return [
            'access' => [
                'class' => AccessControl::class,
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
                'class' => VerbFilter::class,
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
				->andFilterWhere(['active'=>1, 'sharing' => 1])
				->andFilterWhere(['<=', '`date`',date('Y-m-d')])
				->orderBy('date DESC')
				->limit(200)
				->all()
        ]);
    }
    
     public function actionView()
    {
         $track = Track::find()
                ->andFilterWhere(['= BINARY', 'url', Yii::$app->request->get('link')])
                ->andFilterWhere(['active' => 1])
                ->andFilterWhere(['<=', '`date`', date('Y-m-d')])
                ->one();

         if(!$track) {
             return $this->redirect(['index']);
         }

		$referal = !empty($_SESSION["referal"]) ? $_SESSION["referal"] : '';

         if (empty($referal)) {
			 $referal = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		 }

		$track->views = (int)($track->views+1);
		$track->save();
        $track->setLog($referal);

        $this->view->registerMetaTag(['name' => 'description', 'content' => 'Listen, download or stream '.$track->name.'!', 'data-hid'=>'description'],'description');
        $this->view->registerMetaTag(['property' => 'og:url', 'content' => '/'.$track->url], 'og:url');
        $this->view->registerMetaTag(['property'=>'og:title', 'content' => $track->artist_name.' - '.$track->name.' | BlckLink'], 'og:title');
        $this->view->registerMetaTag(['property'=>'og:description', 'content' => 'Listen, download or stream '.$track->name.'!'], 'og:description');
        $this->view->registerMetaTag(['property' => 'og:image:width', 'content' => '200'],'og:image:width');
        $this->view->registerMetaTag(['property' => 'og:image:height', 'content' => '200'],'og:image:height');
        $this->view->registerMetaTag(['property' => 'og:image', 'content' => $track->getImage()],'og:image');

        $this->view->registerMetaTag(['name' => 'twitter:card', 'content' => 'summary_large_image'],'twitter:card');
        $this->view->registerMetaTag(['name' => 'twitter:site', 'content' => '@'.$track->url],'twitter:site');
        $this->view->registerMetaTag(['name' => 'twitter:title', 'content' => $track->artist_name.' - '.$track->name.' | BlckLink'],'twitter:title');
        $this->view->registerMetaTag(['name' => 'twitter:description', 'content' => 'Listen, download or stream '.$track->name.'!'],'twitter:description');
        $this->view->registerMetaTag(['name' => 'twitter:image', 'content' =>  $track->getImage()],'twitter:image');

        return $this->render('view', [
            'track' => $track,
            'services' => (object) unserialize($track->servise),
        ]);
    }

    public function actionAjax()
    {
        if(Yii::$app->request->isAjax) {
            if(Yii::$app->request->post('method') == 'servise'
                || Yii::$app->request->post('method') == 'link'
            ) {
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

                        $log->country = $country ?? null;
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
        return $this->redirect(['index']);
        //return $this->renderPartial('about');
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
    public function actionSitemap() {
        
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

    public function actionTelegram()
    {
        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->getRawBody();
            $update = json_decode($data, true);

           // Yii::info($update, 'telegram');
            
            if (false /*isset($update['callback_query'])*/) {
                $chatId = $update['callback_query']['message']['chat']['id'];
                $callbackData = $update['callback_query']['data'];
                
                switch ($callbackData) {
                    case 'is_manager':
                        $admin = User::findOne(['telegram_id' => $chatId]);
                        
                        if ($admin) {
                            Yii::$app->cache->delete('await_manager_code_' . $chatId);
                            $text = "<b>Привіт {$admin->getFullName()}!</b>\n\n"
                            . "<i>Функції для менеджерів наразі знаходяться на стадії розробки</i>\n\n"
                            . "Але не переживай, я продовжу надсилати тобі повідомлення як і раніше";
                            
                            
                            $this->sendMessage($chatId, $text, null, 'HTML');
                            exit();
                        }
                        
                        Yii::$app->cache->set('await_manager_code_' . $chatId, true);
                        $this->sendMessage($chatId, "Введи свій персональний код:");
                        break;
                    case 'is_artist':
                        $artist = Artist::findOne(['telegram_id' => $chatId]);
                        
                        if ($artist) {
                            $text = "<b>Привіт {$artist->name}!</b>\n"
                                . "Чим можу допомогти?";
                            
                            $keyboard = [
                                'inline_keyboard' => [
                                    [
                                        ['text' => 'Звіт по трекам', 'callback_data' => 'get_track_info'],
                                        ['text' => 'Звіт по балансу', 'callback_data' => 'get_balance_info']
                                    ],
                                ]
                            ];
                            
                            $this->sendMessage($chatId, $text, $keyboard, 'HTML');
                            exit();
                        }
                        
                        Yii::$app->cache->set('await_artist_code_' . $chatId, true);
                        $this->sendMessage($chatId, "Введи КОД:");

                        break;
                    case 'get_track_info':
                        $artist = Artist::findOne(['telegram_id' => $chatId]);
                        
                        if ($artist) {
                            $text = $artist->getTrackReport();
                            $keyboard = [
                                'inline_keyboard' => [
                                    [
                                        ['text' => 'Звіт по трекам', 'callback_data' => 'get_track_info'],
                                        ['text' => 'Звіт по балансу', 'callback_data' => 'get_balance_info']
                                    ],
                                    /* [
                                         ['text' => 'Ввести email', 'callback_data' => 'enter_email']
                                     ],
                                     [
                                         ['text' => 'Налаштування', 'callback_data' => 'settings']
                                     ]*/
                                ]
                            ];
                            $this->sendMessage($chatId, $text, $keyboard, 'HTML');
                        } else {
                            $keyboard = [
                                'inline_keyboard' => [
                                    [
                                        ['text' => 'Ввести код', 'callback_data' => 'is_artist'],
                                    ],
                                ]
                            ];
                            $this->sendMessage($chatId, "Артист не знайдений.\n Будь ласка, пройди ідентифікацію ще раз.", $keyboard, 'HTML');
                        }
                        
                        break;
                    case 'get_balance_info':
                        $artist = Artist::findOne(['telegram_id' => $chatId]);
                        if ($artist) {
                            $text = $artist->getBalanceReport();
                            $keyboard = [
                                'inline_keyboard' => [
                                    [
                                        ['text' => 'Звіт по трекам', 'callback_data' => 'get_track_info'],
                                        ['text' => 'Звіт по балансу', 'callback_data' => 'get_balance_info']
                                    ],
                                ]
                            ];
                            $this->sendMessage($chatId, $text, $keyboard, 'HTML');
                        } else {
                            $keyboard = [
                                'inline_keyboard' => [
                                    [
                                        ['text' => 'Ввести код', 'callback_data' => 'is_artist'],
                                    ],
                                ]
                            ];
                            $this->sendMessage($chatId, "Артист не знайдений.\n Будь ласка, пройди ідентифікацію ще раз.", $keyboard, 'HTML');
                        }
                        
                        break;
                    case 'confirm_artist_id':
                        break;
                    case 'cancel_confirm_artist_id':
                        break;
                }
            }
            
            // Обробка текстових повідомлень
            if (false /*isset($update['message'])*/) {
                $chatId = $update['message']['chat']['id'];
                $text = trim($update['message']['text']);

                // Deep-link format: /start <artist_id>

               // preg_match('/^\/start(?:\s+(.+))?$/', $text, $matches);

              //  $this->sendMessage($chatId, $text);
              //  exit;
                if (false /*isset($matches[1])*/) {
                    $payload = isset($matches[1]) ? trim($matches[1]) : '';

                    if (!empty($payload)) {

                        if (!ctype_digit($payload)) {
                            $this->sendMessage($chatId, "Некоректний ідентифікатор артиста.");
                            exit;
                        }

                        $artistId = (int)$payload;
                        $artist = Artist::findOne(['id' => $artistId]);

                        if (!$artist) {
                            $this->sendMessage($chatId, "Артиста не знайдено за вказаним посиланням.");
                            exit;
                        }

                        // Prevent binding one chat to different artists.
                        $alreadyLinkedArtist = Artist::find()
                            ->where(['telegram_id' => $chatId])
                            ->andWhere(['<>', 'id', $artist->id])
                            ->one();

                        if ($alreadyLinkedArtist) {
                            $this->sendMessage(
                                $chatId,
                                "Цей Telegram вже прив'язаний до іншого артиста ({$alreadyLinkedArtist->name})."
                            );
                            exit;
                        }

                        if (!empty($artist->telegram_id) && (string)$artist->telegram_id !== (string)$chatId) {
                            $this->sendMessage($chatId, "Цей артист вже прив'язаний до іншого Telegram акаунта.");
                            exit;
                        }

                        $artist->telegram_id = $chatId;
                        $artist->save(false, ['telegram_id']);

                        $keyboard = [
                            'inline_keyboard' => [
                                [
                                    ['text' => 'Звіт по трекам', 'callback_data' => 'get_track_info'],
                                    ['text' => 'Звіт по балансу', 'callback_data' => 'get_balance_info']
                                ],
                            ]
                        ];

                        $welcomeText = "<b>Привіт {$artist->name}!</b>\n"
                            . "Твій Telegram успішно прив'язано.\n\n"
                            . "<b>Я можу надати тобі таку інформацію:</b>\n"
                            . "• <code>Звіт по трекам</code>\n"
                            . "• <code>Звіт по балансу</code>\n";

                        $this->sendMessage($chatId, $welcomeText, $keyboard, 'HTML');
                        exit;
                    }
                }

                // Якщо бот чекає email
                if (Yii::$app->cache->get('await_artist_code_' . $chatId)) {
                    if (filter_var($text, FILTER_SANITIZE_ADD_SLASHES) && strlen($text) == 10) {
                       // Yii::$app->cache->set('email_' . $chatId, $text);
                        $artist = Artist::findOne(['telegram_code' => $text]);
                        
                        if ($artist) {
                            if ($artist->telegram_id && $artist->telegram_id != $chatId) {
                                $this->sendMessage($chatId, "Не вірний код!");
                                Yii::$app->cache->delete('await_artist_code_' . $chatId);
                                exit;
                                
                            }
                            
                            $artist->telegram_id = $chatId;
                            $artist->save(false);
                            
                            Yii::$app->cache->delete('await_artist_code_' . $chatId);
                            $keyboard = [
                                'inline_keyboard' => [
                                    [
                                        ['text' => 'Звіт по трекам', 'callback_data' => 'get_track_info'],
                                        ['text' => 'Звіт по балансу', 'callback_data' => 'get_balance_info']
                                    ],
                                   /* [
                                        ['text' => 'Ввести email', 'callback_data' => 'enter_email']
                                    ],
                                    [
                                        ['text' => 'Налаштування', 'callback_data' => 'settings']
                                    ]*/
                                ]
                            ];
                            
                            
                            $text = "<b>Привіт {$artist->name}!</b>\n"
                                . "От ми і познайомились, дуже приємно.\n\n"
                                . "<b>Я можу надати тобі таку інформацію:</b>\n"
                                . "• <code>Звіт по трекам</code>\n"
                                . "• <code>Звіт по балансу</code>\n";
                            
                            $this->sendMessage($chatId, $text, $keyboard, 'HTML');
                        } else {
                            $this->sendMessage($chatId, "Артист не знайдений.\n Будь ласка, введи корректний код",);
                        }
                    } else {
                        Yii::$app->cache->delete('await_artist_code_' . $chatId);
                        $this->sendMessage($chatId, "Невірний код. Спробуй спочатку.");
                    }
                    exit;
                } else if (Yii::$app->cache->get('await_manager_code_' . $chatId)) {
                    if (filter_var($text, FILTER_SANITIZE_ADD_SLASHES)) {
                        // Yii::$app->cache->set('email_' . $chatId, $text);
                        $this->sendMessage($chatId, "Цей функціонал ще в розробці.");
                        Yii::$app->cache->delete('await_manager_code_' . $chatId);
                    } else {
                        $this->sendMessage($chatId, "Невірний формат коду. Спробуй ще раз.");
                    }
                    Yii::$app->cache->delete('await_manager_code_' . $chatId);
                    exit;
                }
            }

            Command::run("/start", function($telegram) {

                $data = [
                    'from' => get_object_vars($telegram->input->message->from),
                    'chat' => get_object_vars($telegram->input->message->chat),
                ];


                $text = $telegram->input->text;
                $code_1 = preg_replace('/\D+/', '', $text);



                if (preg_match('/^\/start\s+(\d+)$/', trim($text), $m)) {
                    $code = $m[1]; // "12345"
                } else {
                    $code = null; // формат не підійшов
                }

                $this->sendMessage($telegram->input->message->chat->id, $code_1. ' - ' .$code);
                exit();

                if ($code) {
                    $artist = Artist::findOne(['telegram_code' => $code]);
                    if ($artist) {
                        $this->sendMessage($telegram->input->message->chat->id, $code, null, 'HTML');
                        exit();
                    }
                }

                file_put_contents(
                    'test.txt',
                    print_r($data, 1),
                    FILE_APPEND
                );
                
                $artist = Artist::findOne(['telegram_id' => $telegram->input->message->chat->id]);
                
                if ($artist) {
                    $data = [
                        'chat_id' => $telegram->input->message->chat->id,
                        "text" => "<b>Привіт {$artist->name}!</b>\n"
                        . "Чим можу допомогти?",
                        'parse_mode' => 'HTML',
                        'reply_markup' => json_encode([
                            'inline_keyboard' => [
                                [
                                    ['text' => 'Звіт по трекам', 'callback_data' => 'get_track_info'],
                                    ['text' => 'Звіт по балансу', 'callback_data' => 'get_balance_info']
                                ],
                            ]
                        ]),
                    ];
                } else {
                    $data = [
                        'chat_id' => $telegram->input->message->chat->id,
                        "text" => "<b>Привіт {$telegram->input->message->from->first_name}!</b>\n"
                            . "Щоб продовжити спілування, давай познайомимось.\n"
                        . "Введи будь ласка свій персональний код.",
                        'parse_mode' => 'HTML',
                        'reply_markup' => json_encode([
                            'inline_keyboard'=>[
                                [
                                    ['text' => 'Ввести код', 'callback_data' => 'is_artist'],
                                   // ['text' => 'Я менеджер', 'callback_data' => 'is_manager'],
                                    //['text' => 'Відправити email', 'callback_data' => 'enter_email'],
                                ]
                            ]
                        ]),
                    ];
                }

                $telegram->sendMessage($data);
            });

            exit();
        }

        return $this->redirect(['index']);
    }
    
    
    private function sendMessage($chatId, $text, $keyboard = null, $parse_mode = null): void
    {
        $data = [
            'chat_id' => $chatId,
            'text' => $text
        ];
        
        if ($keyboard) {
            $data['reply_markup'] = json_encode($keyboard);
        }
        
        if ($parse_mode) {
            $data['parse_mode'] = $parse_mode; // HTML або MarkdownV2
            
        }
        /* @var $client Telegram */
        $client = Yii::$app->telegram;
        
        $client->sendMessage($data);
    }
    
    
    
}
