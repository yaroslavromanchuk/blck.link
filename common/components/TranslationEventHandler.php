<?php

namespace common\components;

use yii\i18n\MissingTranslationEvent;
use Yii;
class TranslationEventHandler
{
    
    public static function handleMissingTranslation(MissingTranslationEvent $event) {
      
      //  $event->translatedMessage = "@MISSING: {$event->category}.{$event->message} FOR LANGUAGE {$event->language} @";
       $mes = new \common\models\Message();
       $mes->name = $event->message;
       $mes->translate = $event->message;
       $mes->lang_id = \common\models\Lang::findOne(['local'=>$event->language])->id;
       $mes->save();
    }
}