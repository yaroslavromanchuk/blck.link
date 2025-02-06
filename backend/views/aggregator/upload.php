<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\UploadReport */

$this->title = 'Завантаження звітів';

$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Агрегатори'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Yii::t('app', 'Завантаження звіту');
?>
<div class="aggregator-update row">
    <div id="message"></div>
    <div class="row" id="upload_area">
        <?php
        $form = ActiveForm::begin([
                'id' => 'upload_file',
                'options' => [
                    'enctype' => 'multipart/form-data',
                ]
            ]) ?>
            <div class="col-md-3">
            <?= $form->field($model, 'aggregatorId')
                ->dropDownList(\backend\models\Aggregator::find()
                ->select(['name', 'aggregator_id'])
                ->indexBy('aggregator_id')
                ->column()) ?>
            </div>
            <div class="col-md-1">
                <?= $form->field($model, 'quarter')
                    ->dropDownList([1 => 1, 2 => 2, 3 => 3, 4 => 4]) ?>
            </div>
            <div class="col-md-1">
                <?= $form->field($model, 'year')
                    ->dropDownList([2024 => 2024, 2025 => 2025]) ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($model, 'file')->fileInput() ?>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <?= Html::submitButton(Yii::t('app', 'Завантажити'), [ 'class' => 'btn btn-success']) ?>
                </div>
            </div>
        <?php ActiveForm::end() ?>
    </div>
    <div class="table-responsive" id="process_area"></div>
</div>
<?php
$script = <<< JS
$(function() {
    $('#upload_file').on('beforeSubmit', function(e) {
        e.preventDefault();
        var form = $(this);
        console.log(form);
    var formData = new FormData(this);
    console.log(formData.entries());
    
    $.ajax({
        url: $(this).attr("action"),
        method: 'POST',
        data: formData,
        dataType:'html',
        contentType:false,
        //cache:false,
        processData:false,
        success:function(data, textStatus, jqXHR) {
                $('#process_area').html(data);
                $('#upload_area').css('display', 'none');
        }, error:function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        }
    });
        
    }).on('submit', function(e){
        e.preventDefault();
    });

    var total_selection = 0;
    var isrc = 0;
    var date_report = 0;
    var platform = 0;
    var count = 0;
    var amount = 0;
    var column_data = [];

    $(document).on('change', '.set_column_data', function() {
        var column_name = $(this).val();
        var column_number = $(this).data('column_number');

        if(column_name in column_data) {
            alert('Ви вже визначили стовпець' + column_name);

            $(this).val('');

            return false;
        } else if(column_name !== '') {
            column_data[column_name] = column_number;
        } else {
            const entries = Object.entries(column_data);

            for(const [key, value] of entries)
            {
                if(value === column_number)
                {
                    delete column_data[key];
                }
            }
        }

        total_selection = Object.keys(column_data).length;

        if(total_selection === 5) {
            $('#import').attr('disabled', false);
            isrc = column_data.isrc;
            date_report = column_data.date_report;
            platform = column_data.platform;
            count = column_data.count;
            amount = column_data.amount;
        } else {
            $('#import').attr('disabled', 'disabled');
        }
    });

    $(document).on('click', '#import', function(event) {
        event.preventDefault();
        $.ajax({
            url:"/aggregator/upload-import",
            method:"POST",
            data:{isrc:isrc, date_report:date_report, platform:platform, count:count, amount:amount},
            beforeSend:function(){
                $('#import').attr('disabled', 'disabled');
                $('#import').text('Importing...');
            },
            success:function(data) {
                console.log(data);
                $('#import').attr('disabled', false);
                $('#import').text('Import');
                $('#process_area').css('display', 'none');
                $('#upload_area').css('display', 'block');
                //$('#upload_form')[0].reset();
                $('#message').html("<div class='alert alert-success'>"+data.message+"</div>");
            }, error:function(jqXHR, textStatus, errorThrown) {
                console.log(jqXHR);
                console.log(textStatus);
                console.log(errorThrown);
            }
        })
    });
    });
JS;
$this->registerJs($script);
