<?php
namespace frontend\models;
 
use Yii;
use yii\base\Model;
class Sitemap extends Model{
    
    public function getUrl(){
            $urls = [];
            //статичні сторінки
            $url_menus = Track::find()
                    ->select('url')
                    ->where(['active'=> 1])
                    ->all();
            //Формируем двумерный массив. createUrl преобразует ссылки в правильный вид. 
            //Добавляем элемент массива 'daily' для указания периода обновления контента 
             foreach ($url_menus as $url_menu){
                $urls[] = ['loc' => Yii::$app->urlManager->createUrl([$url_menu->url]), 'changefreq'=>'daily', 'priority'=> 0.9];
            }
           
            
            return $urls;
    }
 
    //Формирует XML файл, возвращает в виде переменной
    public function getXml($urls){
        
        $host = Yii::$app->request->hostInfo; // домен сайта    
        ob_start();  
        echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
        <url>
        <loc><?=$host.Yii::$app->urlManager->createUrl(['site/index'])?></loc>
        <lastmod><?=date('Y-m-d')?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1</priority>
        </url>
        <?php foreach($urls as $url): ?>
        <url>
        <loc><?=$host.$url['loc']?></loc>
        <lastmod><?=date('Y-m-d')?></lastmod>
        <changefreq><?=$url['changefreq']?></changefreq>
        </url>
        <?php endforeach; ?>
        </urlset>
        <?php return ob_get_clean();
    }
}