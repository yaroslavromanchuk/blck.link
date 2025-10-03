<?php

use yii\bootstrap\Modal;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\widgets\ListView;
use yii\helpers\Url;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\TrackSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Треки');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="track-index">
     <?php if(Yii::$app->user->can('label')){ ?>
    <p class="text-right">
        <?= Html::a(Yii::t('app', 'Додати трек'), ['create'], ['class' => 'btn btn-danger']) ?>
        <a href="<?=Url::to(['track/export-track'])?>" class="btn btn-warning" title="Скачати список треків укр. артистів"><span class="glyphicon glyphicon-download-alt"></span></a>
    </p>
     <?php } ?>

    <?php //Pjax::begin([ 'enablePushState' => true]);
    Modal::begin([
        'id' => 'percentage-modal',
        'header' => '<h4>Відсотки</h4>',
    ]);

    echo '<div id="modalContent"></div>';

    Modal::end();
    // ?>
    <?=$this->render('_search', ['model' => $searchModel]) ?>

    <?=ListView::widget([
        'dataProvider' => $dataProvider,
        'itemView' => '_list',
        'options' => [
            'tag' => 'div',
            'class' => 'row',
          //  'style' => 'padding-top: 15px;'
        ],
        'itemOptions' => [
             'tag' => 'div',
             'class' => 'col-xs-12',
             'style' => ''
        ],
        'layout' => "{sorter}\n{items}\n{summary}\n{pager}",
    ])?>
    <?php /* echo GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
[
           // 'label' => 'Фото',
            'attribute' => 'img',
            'format' => 'raw',
            'value' => function($data){
        $list = '<div class="trumb_foto">'
                . ''.Html::img(
                        $data->getImage(),['alt'=>'yii2 - картинка в gridview','style' => 'width:60px; padding:1px;']).''
                    .'</div>';
                 return $list;
            },
        ],
           // 'id',
            //'artist_id',
            'artist',
            
            'name',
            'date',
            
            //'img',
            'url',
            //'youtube',
            'tag',
            //'sharing',
            'views',
            'click',
            //'active',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]);*/ ?>

    <?php //Pjax::end(); ?>
</div>
<?php
$script = <<< JS
$(function() {
    
     $(document).on('click', '.showModalButton', function(){
         $('#percentage-modal')
         .find('#modalContent')
         .load($(this).attr('href'));
     });
    
    });
JS;
$this->registerJs($script);
