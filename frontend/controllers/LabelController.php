<?php

namespace frontend\controllers;

use Yii;
use common\models\SubLabel;
use frontend\models\Track;
use yii\web\NotFoundHttpException;

class LabelController extends \yii\web\Controller
{
    public function actionIndex()
    {
        return $this->render('index',[
            'list' => SubLabel::find()
                ->andFilterWhere(['active' => 1,])
                ->limit(100)
                ->all()
        ]);
    }

    public function actionList($url)
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

    public function actionView(string $track)
    {
        $track = Track::find()
            ->andFilterWhere(['= BINARY', 'url', $track])
            //->andFilterWhere(['active' => 1])
            //->andFilterWhere(['<=', '`date`', date('Y-m-d')])
            ->one();

        if(!$track) {
            return $this->redirect(['/']);
        }

        $referal = !empty($_SESSION["referal"]) ? $_SESSION["referal"] : '';

        if (empty($referal)) {
            $referal =  !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        }

        $track->views = (int)($track->views+1);
        $track->save();

        $view = new \common\models\Views();
        $view->track_id = $track->id;
        $view->view = 1;
        $view->ip = Yii::$app->request->userIP;
        $country = geoip_country_name_by_name(Yii::$app->request->userIP);
        $view->country = $country ?? null;
        $view->referal = $referal;
        $view->data = date("Y-m-d");
        $view->save();

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
        ]);
    }

    protected function findModel($url): ?SubLabel
    {
        if (($model = SubLabel::findOne(['url' => $url])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'The requested page does not exist.'));
    }
}
