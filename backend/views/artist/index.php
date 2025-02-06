<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use  yii\helpers\Url;

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
    <a href="<?=Url::to(['artist/calculate-deposit', 'id' => null, 'url' => '/artist/index'])?>" class="btn btn-danger" style="position: absolute;right: 0px; margin-top: -40px;">Депозит
        <span class="badge">UAH: <?=$sumDepositUAH?></span>
        <span class="badge">EURO: <?=$sumDepositEURO?></span>
    </a>
</div>
<div class="artist-index">
    <p>
        <?= Html::a(Yii::t('app', 'Додати артиста'), ['create'], ['class' => 'btn btn-success']) ?>
        <?= Html::button('Створити інвойс на виплату', ['class' => 'btn btn-info', 'id' => 'generate', 'data-toggle' => 'modal', 'data-target' => '#invoice-add-modal']) ?>
    </p>

    <?php Pjax::begin(); ?>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?php

    $total_amount = $total_amount_uah = 0;

    foreach($dataProvider->models as $m)
    {
        $total_amount += $m->deposit_1;
        $total_amount_uah += $m->deposit;
    }
    ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'showFooter' => true,
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

           // 'id',
           // 'logo:raw',
            [
            'label' => 'Фото',
            'attribute' => 'logo',
            'format' => 'raw',
            'value' => function($data) {
				    return !empty($data->logo) ? '<div class="trumb_foto"> ' . Html::img($data->getLogo(),['alt' => 'logo', 'style' => 'border-radius: 50%;width:50px; padding:1px;']) .'</div>' : '';
                },
            ],
            'name',
            //'phone',
           // 'email:email',
           [ // name свойство зависимой модели owner
                'attribute' => 'reliz',
                'label' => Yii::t('app', 'Треків'),
                'value' => function($data) { return $data->getTracks()->count(); },
           ],
            'percentage',
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
            'date_last_payment',
            //'active',

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

    <?php Pjax::end(); ?>

</div>
<?= \backend\widgets\CreateInvoice::widget(); ?>
<?php
$script = <<< JS
jQuery(function($) {
    
    $('#invoice-add-modal').on('show.bs.modal', function (event) {
         var keys = jQuery('#w0').yiiGridView("getSelectedRows");
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
       var keys = jQuery('#w0').yiiGridView("getSelectedRows");
       
       if (keys.length > 0) {
           alert(keys);
          
       } else {
             alert('Не вибрано жодного артиста');
       }
       
      
      /* $.ajax({
         url: "'. \yii\helpers\Url::toRoute('delete') .'",
         type: "POST",
         data: {id: keys},
         success: function(){
            alert("yes")
         }
       });*/
   });
    
   /// jQuery('.select-on-check-all, .checkbox-row').click(function() {
   //     console.log(jQuery('#w0').yiiGridView('getSelectedRows'));
    //});
    });
JS;
$this->registerJs($script);
