<?php

use common\models\SubLabel;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\ArtistSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $sumDepositUAH float */
/* @var $sumDepositEURO float */

$this->title = Yii::t('app', 'Контрагенти');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="page-header">
    <h1><?= Html::encode($this->title) ?></h1>
    <a href="<?=Url::to(['artist/calculate-deposit', 'id' => null, 'url' => '/artist/index'])?>" class="btn btn-danger" style="position: absolute;right: 0px; margin-top: -40px;">Перерахувати допозити
        <!--<span class="badge">UAH: <?php //$sumDepositUAH ?></span>
        <span class="badge">EURO: <?php //$sumDepositEURO ?></span>-->
    </a>
</div>
<div class="artist-index">
    <p>
        <?= Html::a(Yii::t('app', 'Додати контрагента'), ['create'], ['class' => 'btn btn-success']) ?>
        <?= Html::button('Створити інвойс на виплату', ['class' => 'btn btn-info', 'id' => 'generate', 'data-toggle' => 'modal', 'data-target' => '#invoice-add-modal']) ?>
        <a href="<?=Url::to(['artist/export-artist'])?>" class="btn btn-warning">Скачати список укр. артистів</a>
    </p>

    <?php //Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?php
    $selected = [];
    $total_amount = $total_amount_uah = $total_amount_usd = 0;

    foreach($dataProvider->models as $m)
    {
        if ($m->id !=0) {
            $total_amount += $m->deposit_1;
            $total_amount_uah += $m->deposit;
            $total_amount_usd += $m->deposit_3;
        }
    }
    
    $labelList = SubLabel::getDb()->cache(function ($db) {
        // Запит, результат якого буде кешовано
        return SubLabel::find()->select(['name', 'id'])
            ->where(['active' => 1])
            ->indexBy('id')
            ->column();
    }, 3600); // Кешування на 1 годину (3600 секунд)
    
    
    $countries = Yii::$app->cache->getOrSet('countries_map', function () {
        return ArrayHelper::map(
            \backend\models\Country::find()->select(['country_id', 'country_name'])->asArray()->all(),
            'country_id',
            'country_name'
        );
    }, 3600); // Кешування на 1 годину (3600 секунд)
    ?>


    <?php Pjax::begin([
    'timeout' => 5000,
    'enablePushState' => false,
    ]);
    ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'showFooter' => true,
		'rowOptions' => function ($model, $key, $index, $grid)
		{
			if ($model->label_id == 0 && $model->notify && (empty($model->email) || !filter_var($model->email, FILTER_VALIDATE_EMAIL))) {
                return ['class' => 'danger'];
			}
		},
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'class' => 'yii\grid\CheckboxColumn',
                'checkboxOptions' => static function ($model) {
                        return [
                            'value' => $model->id,
                            'disabled' => !$model->id,
                        ];
                    }
            ],
            [
                'label' => 'Фото',
                'attribute' => 'logo',
                'format' => 'raw',
                'value' => function($data) {
                    return !empty($data->logo)
                        ? '<div class="trumb_foto"> ' . Html::img($data->getLogo(),[
                            'loading' => 'lazy', 'alt' => 'logo', 'style' => 'border-radius: 50%;width:50px; padding:1px;']) .'</div>'
                        : '';
                },
            ],
            'name:ntext',
            //'full_name:ntext',
            [
                'attribute' => 'label_id',
                'format' => 'raw',
                'filter' => Select2::widget([
                   // 'name' => 'ArtistSearch[label_id]',
                    'attribute' => 'label_id',
                    'model' => $searchModel,
                    'language' => 'uk',
                    'data' => $labelList,
                    'options' => [
                       // 'multiple' => true,
                        'placeholder' => 'Виберіть лейб...',
                        'options' => $selected,
                        //'value' => 8,
                    ],
                    'pluginOptions' => [
                        'allowClear' => true,
                    ],
                ]),
                'value' => function($data) {
                    return $data->label->name;
                }
            ],
            [
                'attribute' => 'type_id',
                'filter' => [1 => 'Артист', 2 => 'Партнер'],
                'value' => function($data) {
                    return $data->clientType->name;
                }
            ],
            [ // name свойство зависимой модели owner
                'attribute' => 'reliz',
                'label' => Yii::t('app', 'Треків'),
                'value' => fn($model) => $model->tracks_count,
            ],
           /* [
                'attribute' => 'percentage',
                'value' => function($data) { return $data->isSubLabel() ? 'N/A' : $data->percentage; },
            ],*/
            [
                'attribute' => 'deposit',
                'label' => 'Депозит UAH >=',
                'value' => function($data) { return $data->deposit; },
                'footer' => $total_amount_uah
            ],
            [
                'attribute' => 'deposit_1',
                'label' => 'Депозит EURO >=',
                'value' => function($data) { return $data->deposit_1; },
                'footer' => $total_amount
            ],
            [
                'attribute' => 'deposit_3',
                'label' => 'Депозит USD >=',
                'value' => function($data) { return $data->deposit_3; },
                'footer' => $total_amount_usd
            ],
            [
                    'attribute' => 'country_id',
                    'value' => function($data) { return $data->country_id ? $data->country->country_name : ''; },
                    'filter' => ArrayHelper::map($countries, 'country_id', 'country_name')
            ],
            'notify:boolean',
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => Yii::$app->user->can('admin') ? '{view} {update} {delete} {export-act} {mail}': '{view} {update} {export-act} {mail}',
                'buttons' => [
                    'export-balance' => function ($url, $model, $key) {
                        return Html::a('<span class="glyphicon glyphicon-paste"></span>', $url, [

                            'title' => Yii::t('yii', 'Export Balance'),
                            'target' => '_blank'
                        ]);
                    },
                    'export-act' => function ($url, $model, $key) {
                        return Html::a('<span class="glyphicon glyphicon-paste" style="margin-left: 20px"></span>', $url, [

                            'title' => Yii::t('yii', 'Export Report'),
                            'target' => '_blank'
                        ]);
                    },
                    'delete' => function ($url, $model, $key) {
                        return Html::a('<span class="glyphicon glyphicon-trash"></span>', $url, [
                            'title' => Yii::t('yii', 'Delete'),
                            'data' => [
                                'confirm' => 'Видалити артиста разом з усіма релізами та треками? Дію скасувати буде неможливо!',
                                'method' => 'post',
                            ],
                        ]);
                    },
                    'mail' => function ($url, $model, $key) {
                        /** @var \backend\models\Artist $model */
                        $titleLog = 'Відправити звіт';
                        $logs = $model->getInvoiceLogs('Balance Notification');
                        $logs = array_filter($logs, function($log) {
                            return date('m-Y',strtotime($log->date_added)) == date('m-Y');
                        });
                        
                        if (count($logs)) {
                            $titleLog = "Відправлено звіт в такі дати:\n";
                            
                            foreach ($logs as $log) {
                                $titleLog .= date('d.m.Y H:i:s', strtotime($log->date_added)) . "\n";
                            }
                        }
                
                        return Html::a('<span class="glyphicon glyphicon-envelope" data-toggle="tooltip" data-placement="top" data-title=" ' . $titleLog. '"></span>', $url, [
                            'title' => Yii::t('yii', 'Відпрвити звіт'),
                            'class' => 'btn btn-xs send-report',// . ($model->hasNotified() ? ' hidden' : ''),
                            'data-pjax' => '1',
                            'disabled' => $model->hasNotified(),
                            'style' => !$model->notify ? 'display:none' : '',
                            'data-id' => $model->id,
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>

</div>
<?=\backend\widgets\CreateInvoice::widget();?>
<?php
$script = <<< JS
jQuery(function($) {
    
    $('#invoice-add-modal').on('show.bs.modal', function (event) {
         var keys = jQuery('.grid-view').yiiGridView("getSelectedRows");
        if (keys.length > 0) {
            $(this).find('#invoice-artist_ids').val(keys);
       } else {
             alert('Не вибрано жодного артиста');
             
             return false;
       }
    });
    
   /* $("#generate1").on("click", function(e) {
       e.preventDefault()
       var keys = jQuery('.grid-view').yiiGridView("getSelectedRows");
       
       if (keys.length > 0) {
           alert(keys);
       } else {
             alert('Не вибрано жодного артиста');
       }
   });*/
    });

$(document).on('click', '.send-report', function(e) {
    e.preventDefault();
    const btn = $(this);

    $.get(btn.attr('href'))
        .done(() => btn.hide())
        .fail(() => alert('Помилка'));
});

JS;
$this->registerJs($script);
