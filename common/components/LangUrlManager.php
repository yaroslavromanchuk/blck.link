<?php
/**
 * Description of LangUrlManager
 *
 * @author PHP
 */
namespace common\components;

use Yii;
use yii\web\UrlManager;
use common\models\Lang;

class LangUrlManager extends UrlManager {
    
    public function createUrl($params)
    {
        $url = parent::createUrl($params);
        
        if (isset($params['lang'])) {
            //текущий язык приложения
             $curentLang = Lang::findOne($params['lang']);
            if( $curentLang === null ){
                $curentLang = Lang::getDefaultLang();
            }

            //Добавляем к URL префикс - буквенный идентификатор языка
            if ($url == '/') {
                return '/' . $curentLang->url;
            } else {
                return '/' . $curentLang->url . $url;
            }
        }else{
             //Если не указан параметр языка, то работаем с текущим языком
            $curentLang = Lang::getCurrent();
            if($curentLang->url != Lang::getDefaultLang()->url){
             return  '/'.$curentLang->url.$url;
            }else{
            return $url;
            }
        }
        
       // return $url == '/' ? '/'.$curentLang->url : '/'.$curentLang->url.$url;
    }
}
