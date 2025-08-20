<?php

namespace common\models;

use InvalidArgumentException;
use Yii;
use yii\base\Model;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "mail_log".
 *
 * @property int $log_id
 * @property int $artist_id
 * @property int $track_id
 * @property int $invoice_id
 * @property string $content
 * @property string $date_added
 * @property string $last_update
 */
class Mail
{
    public array|string $from;
    public array|string $to;
    public array|string $cc = '';
    public array|string $bcc = '';
    public array|string $replyTo = [];
    public string $subject;
    public string $textBody = '';
    public string $htmlBody = '';
    public string|array $attach = '';
    public array|null|string $view = null;
    public array $params = [];


    public function __construct(array $config = [])
    {
        if (isset($config['from'])) {
            $this->from = $config['from'];
        } else {
            $this->from = [Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']];
        }

        if (empty($config['to'])) {
            throw new InvalidArgumentException('"to" must be set');
        }

        if (empty($config['subject'])) {
            throw new InvalidArgumentException('"subject" must be set');
        }

        $this->to = $config['to'];
        $this->subject = $config['subject'];

        if (!empty($config['cc'])) {
            $this->cc = $config['cc'];
        }
        if (!empty($config['bcc'])) {
            $this->bcc = $config['bcc'];
        }
        if (!empty($config['replyTo'])) {
            $this->replyTo = $config['replyTo'];
        }
        if (!empty($config['view'])) {
            $this->view = $config['view'];
        }
        if (!empty($config['params'])) {
            $this->params = $config['params'];
        }
        if (!empty($config['attach'])) {
            $this->attach = $config['attach'];
        }
        if (!empty($config['textBody'])) {
            $this->textBody = $config['textBody'];
        }
        if (!empty($config['htmlBody'])) {
            $this->htmlBody = $config['htmlBody'];
        }

    }

    public function send(string $action, ActiveRecord $model): bool
    {
        $mail = Yii::$app->mailer->compose($this->view, $this->params)
        ->setFrom($this->from)
        ->setTo($this->to)
        ->setSubject($this->subject);

        if (!empty($this->cc)) {
            $mail->setCc($this->cc);
        }
        if (!empty($this->bcc)) {
            $mail->setBcc($this->bcc);
        }
        if (!empty($this->replyTo)) {
            $mail->setReplyTo($this->replyTo);
        }
        if (!empty($this->textBody)) {
            $mail->setTextBody($this->textBody);
        }
        if (!empty($this->htmlBody)) {
            $mail->setHtmlBody($this->htmlBody);
        }
        if (!empty($this->attach)) {
            if (is_array($this->attach)) {
                foreach ($this->attach as $attach) {
                    if (is_array($attach)) {
                        $mail->attach($attach[0], $attach[1]);
                    } else {
                        $mail->attach($attach);
                    }
                }
            } else {
                $mail->attach($this->attach);
            }
        }

        $result = $mail->send();

        if ($result) {
            $log = new MailLog();
            $log->user_id = Yii::$app->user->id;
            $log->content = $action;

            if (!empty($model->artist_id)) {
                $log->artist_id = $model->artist_id;
            }
            if (!empty($model->invoice_id)) {
                $log->invoice_id = $model->invoice_id;
            }
            if (!empty($model->track_id)) {
                $log->track_id = $model->track_id;
            }

            $log->save();
        }

        return $result;
    }
}
