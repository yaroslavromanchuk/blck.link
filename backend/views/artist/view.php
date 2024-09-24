<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use  yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model backend\models\Artist */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Артисти'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
use yii\grid\GridView;
use yii\data\ActiveDataProvider;

$query = new \yii\db\Query();
$invoice = new ActiveDataProvider([
    'query' => $query->from('invoice')
        ->select(['invoice.invoice_id, currency.currency_name, invoice_items.artist_id,
         invoice.invoice_type, track.name as track_name, invoice_items.platform, invoice_type.invoice_type_name,
          SUM(invoice_items.count) as count, SUM(invoice_items.amount) as total, invoice.date_added'])
        ->leftJoin('invoice_items', 'invoice_items.invoice_id = invoice.invoice_id')
        ->leftJoin('invoice_type', 'invoice_type.invoice_type_id = invoice.invoice_type')
        ->leftJoin('track', 'track.id = invoice_items.track_id')
        ->leftJoin('currency', 'currency.currency_id = invoice.currency_id')
        ->where(['invoice_items.artist_id' => $model->id])
        ->groupBy(['invoice.invoice_id']),
    'pagination' => [
        'pageSize' => 20,
    ],
]);

$query = new \yii\db\Query();
$tracks = new ActiveDataProvider([
    'query' => $query->from('track')
        ->select('name, views, click')
        ->where(['artist_id' => $model->id]),
    'pagination' => [
        'pageSize' => 20,
    ],
]);
?>
<div class="page-header">
    <h1>Артист: <?= Html::encode($this->title) ?></h1>
</div>
<div class="artist-view">
    <p class="text-left">
        <?= Html::a(Yii::t('app', 'Редагувати'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?php if(Yii::$app->user->can('admin')){ echo Html::a(Yii::t('app', 'Видалити'), ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ]); } ?>
    </p>
    <div class="row">
        <div class="col-xs-12 col-md-3 col-sm-3">
            <div class="panel panel-default">
                <div class="panel-heading">Превью: <?=$model->name?></div>
                <img class="card-img-top" src="<?=$model->getLogo()?>" alt="Card image cap">
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            //'id',
                            //  'name',
                            'phone',
                            'email:email',
                            //'deposit',
                            [
                                'attribute' => 'deposit',
                                //'label' => 'Релизов',
                                'format'=>'raw',
                                'value' => function($data) {
                                    return "<p id='deposit' style='display: inline'>" . $data->deposit . "</p><button id='deposit_refresh' style='margin-left: 15px' type='button' class='ml-2 btn btn-danger btn-xs' title='Перерахувати'><span class='glyphicon glyphicon-refresh' aria-hidden='true'></span></button>";
                                },
                            ],
                            [
                                'attribute' => 'deposit_1',
                                //'label' => 'Релизов',
                                'format'=>'raw',
                                'value' => function($data) {
                                    return "<p id='deposit_1' style='display: inline'>" . $data->deposit_1 . "</p><button id='deposit_1_refresh' style='margin-left: 15px' type='button' class='ml-2 btn btn-danger btn-xs' title='Перерахувати'><span class='glyphicon glyphicon-refresh' aria-hidden='true'></span></button>";
                                },
                            ],
                            'telegram_id',
                            // 'active',
                            [ // name свойство зависимой модели owner
                                'attribute' => 'admin_id',
                                //'label' => 'Релизов',
                                'value' => function($data) {
                                    return $data->admin->getFullName();
                                },
                            ]
                        ],
                    ]) ?>
            </div>
        </div>
        <div class="col-xs-12 col-md-4 col-sm-4">
            <div class="panel panel-default">
                <div class="panel-heading">Треки</div>
                <div class="panel-body">
                    <?php
                    echo GridView::widget([
                        'dataProvider' => $tracks,
                        'columns' => [
                            [
                                'attribute' => 'name',
                                'label' => 'Трек',
                            ],
                            [
                                'attribute' => 'views',
                                'label' => 'Преглядів',
                            ],
                            [
                                'attribute' => 'click',
                                'label' => 'Кліків',
                            ],
                        ]
                    ]);
                    ?>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-md-5 col-sm-5">
            <div class="panel panel-default">
                <div class="panel-heading">Інвойси</div>
                <div class="panel-body">
                    <?php
                    echo GridView::widget([
                        'dataProvider' => $invoice,
                        'rowOptions' => function ($model)
                        {
                           if($model['invoice_type'] == 1) {
                               return ['class' => 'success'];
                           } else {
                               return ['class' => 'danger'];
                           }
                        },
                        'columns' => [
                            [
                                'attribute' => 'invoice_id',
                                'label' => '№ інвойса',
                            ],
                            [
                                'attribute' => 'invoice_type_name',
                                'label' => 'Тип інвойса',
                            ],
                            [
                                'attribute' => 'currency_name',
                                'label' => 'Валюта',
                            ],
                           /* [
                                'attribute' => 'track_name',
                                'label' => 'Трек',
                            ],
                            [
                                'attribute' => 'platform',
                                'label' => 'Платформа',
                            ],*/
                            [
                                'attribute' => 'total',
                                'label' => 'Дебіт/Кредіт',
                            ],
                            [
                                'attribute' => 'date_added',
                                'label' => 'Дата',
                                'format' => 'date',
                            ],
                            [
                                'class' => 'yii\grid\ActionColumn',
                                'template' => '{view} ',
                                'buttons' => [
                                    'view' => function ($url, $model, $key) {
                                        $t = '/invoice/view-modal?id=' . $model['invoice_id'] . '&artistId=' . $model['artist_id'];
                                        return Html::button('<span class="glyphicon glyphicon-eye-open"></span>', ['value'=> Url::to($t ), 'class' => 'btn btn-default btn-xs custom_button']);
                                    },
                                ],

                                // вы можете настроить дополнительные свойства здесь.
                            ],
                       ]
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php

$this->registerJs(
"
$('#deposit_refresh').on('click', function () {send();});
$('#deposit_1_refresh').on('click', function () {send();});

$(function(){
$('.custom_button').click(function(){
    $('#modalView').modal('show').find('#modalContentView').load($(this).attr('value'));

});});
"
);

\yii\bootstrap\Modal::begin(['id'=>'modalView', 'size'=>'modal-md']);
echo "<div id='modalContentView'></div>";
\yii\bootstrap\Modal::end();
?>

<script type="text/javascript">
function send()
 {
     $.ajax({
         type: 'POST',
         url: '<?php echo Url::to(['artist/calculate-deposit', 'id' => $model->id]); ?>',
         dataType: 'json',
         success: function(data) {
             console.log(data);
             for (const dataKey in data) {
                 $('#' + dataKey).html(data[dataKey]);
             }
         },
         error: function(data) { // if error occured
             alert(data);
         },
     });
}

</script>