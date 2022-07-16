<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ListWidget
 *
 * @author PHP
 */
namespace common\modules\languages\widgets;
//use Yii;
use yii\base\Widget;
//use yii\helpers\Html;
use common\models\Lang;
class ListWidget extends Widget{
    
    public $array_languages;

    public function init() {
        
       /* $language = Yii::$app->language; //текущий язык

        //Создаем массив ссылок всех языков с соответствующими GET параметрами
        $array_lang = [];
        foreach (Yii::$app->getModule('languages')->languages as $key => $value){
            $array_lang += [$value => Html::a($key, ['languages/lang/index', 'lang' => $value])];
        }

        //ссылку на текущий язык не выводим
        if(isset($array_lang[$language])) unset($array_lang[$language]);
        $this->array_languages = $array_lang;
        */
    }

    public function run() {
        return $this->render('list',[
            'current' => Lang::getCurrent(),
            'langs' => Lang::find()->where('id != :current_id', [':current_id' => Lang::getCurrent()->id])->all(),
    ]);
    }
}
