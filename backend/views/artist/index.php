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

$this->title = Yii::t('app', 'Артисты');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="page-header">
    <h1><?= Html::encode($this->title) ?></h1>
    <a href="<?=Url::to(['artist/calculate-deposit', 'id' => null, 'url' => '/artist/index'])?>" class="btn btn-danger" style="position: absolute;right: 0px; margin-top: -40px;">Перерахувати допозити артистам
        <!--<span class="badge">UAH: <?php //$sumDepositUAH ?></span>
        <span class="badge">EURO: <?php //$sumDepositEURO ?></span>-->
    </a>
</div>
<div class="artist-index">
    <p>
        <?= Html::a(Yii::t('app', 'Додати артиста'), ['create'], ['class' => 'btn btn-success']) ?>
        <?= Html::button('Створити інвойс на виплату', ['class' => 'btn btn-info', 'id' => 'generate', 'data-toggle' => 'modal', 'data-target' => '#invoice-add-modal']) ?>
        <a href="<?=Url::to(['artist/export-artist'])?>" class="btn btn-warning">Скачати список артистів</a>
    </p>

    <?php //Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?php
    $selected = [];

  // if(isset($_GET['ArtistSearch']['label_id'])) {
    //   $selected[$_GET['ArtistSearch']['label_id']] = ['selected' => true];
  // }

    $total_amount = $total_amount_uah = $total_amount_usd = 0;

    foreach($dataProvider->models as $m)
    {
        if ($m->id !=0) {
            $total_amount += $m->deposit_1;
            $total_amount_uah += $m->deposit;
            $total_amount_usd += $m->deposit_3;
        }
    }

    $labelList = SubLabel::find()
            ->select(['name', 'id'])
            ->where(['active' => 1])
        ->indexBy('id')
        ->column();
    ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'showFooter' => true,
		'rowOptions' => function ($model, $key, $index, $grid)
		{
			if ($model->notify && (empty($model->email) || !filter_var($model->email, FILTER_VALIDATE_EMAIL))) {
                return ['class' => 'danger'];
			}
		},
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'class' => 'yii\grid\CheckboxColumn',
                'checkboxOptions' =>
                    function($model) {
                        if(!$model->id) {
                            return ['value' => $model->id, 'class' => 'checkbox-row', 'disabled' => true];
                        }else{
                            return ['value' => $model->id, 'class' => 'checkbox-row'];
                        }
                    }
            ],
            [
                'label' => 'Фото',
                'attribute' => 'logo',
                'format' => 'raw',
                'value' => function($data) {
                    return !empty($data->getLogo()) ? '<div class="trumb_foto"> ' . Html::img($data->getLogo(),['alt' => 'logo', 'style' => 'border-radius: 50%;width:50px; padding:1px;']) .'</div>' : '';
                },
            ],
            'name:ntext',
            'full_name:ntext',
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
            [ // name свойство зависимой модели owner
                'attribute' => 'reliz',
                'label' => Yii::t('app', 'Треків'),
                'value' => function($data) { return $data->getTracks()->count(); },
            ],
            [
                'attribute' => 'percentage',
                'value' => function($data) { return $data->isSubLabel() ? 'N/A' : $data->percentage; },
            ],
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
                    'filter' => ArrayHelper::map(\backend\models\Country::find()->asArray()->all(), 'id', 'country_name')
            ],
            'notify:boolean',
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => Yii::$app->user->can('admin') ? '{view} {update} {delete} {export-act}': '{view} {update} {export-act}',
                'buttons' => [
                    'export-balance' => function ($url, $model, $key) {
                        return Html::a('<span class="glyphicon glyphicon-paste" style="margin-left: 20px"></span>', $url, [

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
                ],
            ],
        ],
    ]); ?>

    <?php //Pjax::end(); ?>

</div>
<?=\backend\widgets\CreateInvoice::widget();?>
<?php
$script = <<< JS
jQuery(function($) {
    
    $('#invoice-add-modal').on('show.bs.modal', function (event) {
         var keys = jQuery('.grid-view').yiiGridView("getSelectedRows");
        if (keys.length > 0) {
           var modal = $(this);
            modal.find('#invoice-artist_ids').val(keys);
       } else {
             alert('Не вибрано жодного артиста');
             
             return false;
       }
       
    });
    
    $("#generate1").on("click", function(e) {
       e.preventDefault()
       var keys = jQuery('.grid-view').yiiGridView("getSelectedRows");
       
       if (keys.length > 0) {
           alert(keys);
          
       } else {
             alert('Не вибрано жодного артиста');
       }
   });
    
   /// jQuery('.select-on-check-all, .checkbox-row').click(function() {
   //     console.log(jQuery('#w0').yiiGridView('getSelectedRows'));
    //});
    });
JS;
$this->registerJs($script);
