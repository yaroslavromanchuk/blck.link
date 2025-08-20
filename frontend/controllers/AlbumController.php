<?php

namespace frontend\controllers;

use Yii;
use frontend\models\Albums;
use yii\web\NotFoundHttpException;

class AlbumController extends \yii\web\Controller
{
    public function actionIndex()
    {
        return $this->render('index',[
            'list' => Albums::find()
                ->andFilterWhere(['active' => 1,])
				->orderBy(['id' => SORT_DESC])
                ->limit(100)
                ->all()
        ]);
    }
/*
    public function actionView($url)
    {
        $model = $this->findModel($url);

        $response = [
            'label' => $model,
        ];

        $trackList = $model->user->tracks;

        if (!empty($trackList)) {
            $response['list'] = $trackList;
        }

        return $this->render('list', $response);
    }
*/
    public function actionView(string $url)
    {
        $album = Albums::find()
            ->andFilterWhere(['= BINARY', 'url', $url])
            //->andFilterWhere(['active' => 1])
            //->andFilterWhere(['<=', '`date`', date('Y-m-d')])
            ->one();

        if(!$album) {
            return $this->redirect(['/']);
        }

        $referal = !empty($_SESSION["referal"]) ? $_SESSION["referal"] : '';

        if (empty($referal)) {
            $referal =  !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        }

        $album->views = (int)($album->views+1);
        $album->save();
/*
        $view = new \common\models\Views();
        $view->track_id = $album->id;
        $view->view = 1;
        $view->ip = Yii::$app->request->userIP;
        $country = geoip_country_name_by_name(Yii::$app->request->userIP);
        $view->country = $country ?? null;
        $view->referal = $referal;
        $view->data = date("Y-m-d");
        $view->save();*/

        $this->view->registerMetaTag(['name' => 'description', 'content' => 'Listen, download or stream '.$album->name.'!', 'data-hid'=>'description'],'description');
        $this->view->registerMetaTag(['property' => 'og:url', 'content' => '/'.$album->url], 'og:url');
        $this->view->registerMetaTag(['property'=>'og:title', 'content' => $album->artist->name.' - '. $album->name.' | BlckLink'], 'og:title');
        $this->view->registerMetaTag(['property'=>'og:description', 'content' => 'Listen, download or stream '.$album->name.'!'], 'og:description');
        $this->view->registerMetaTag(['property' => 'og:image:width', 'content' => '200'],'og:image:width');
        $this->view->registerMetaTag(['property' => 'og:image:height', 'content' => '200'],'og:image:height');
        $this->view->registerMetaTag(['property' => 'og:image', 'content' => $album->getImage()],'og:image');

        $this->view->registerMetaTag(['name' => 'twitter:card', 'content' => 'summary_large_image'],'twitter:card');
        $this->view->registerMetaTag(['name' => 'twitter:site', 'content' => '@'.$album->url],'twitter:site');
        $this->view->registerMetaTag(['name' => 'twitter:title', 'content' => $album->artist->name.' - '.$album->name.' | BlckLink'],'twitter:title');
        $this->view->registerMetaTag(['name' => 'twitter:description', 'content' => 'Listen, download or stream '.$album->name.'!'],'twitter:description');
        $this->view->registerMetaTag(['name' => 'twitter:image', 'content' =>  $album->getImage()],'twitter:image');

        return $this->render('view', [
            'album' => $album,
        ]);
    }

    protected function findModel($url): ?Albums
    {
        if (($model = Albums::findOne(['url' => $url])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
