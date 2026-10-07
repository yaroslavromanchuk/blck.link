<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\UploadReport */

$this->title = 'Завантаження звітів';

$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Агрегатори'), 'url' => ['index']];
$this->params['breadcrumbs'][] = Yii::t('app', 'Завантаження звіту');

$years = range(2024, (int) date('Y'), 1);
$years = array_combine($years, $years);
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
                    ->dropDownList($years) ?>
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
    <div class="upload-hint alert alert-info" id="upload_hint">
        Завантажте файл, а потім зіставте колонки у прев’ю перед імпортом.
    </div>

    <div class="upload-status" id="upload_status" style="display:none;">
        <div class="upload-status__text" id="upload_status_text">Завантаження файлу...</div>
        <div class="progress upload-progress">
            <div class="progress-bar progress-bar-striped active" id="upload_progress_bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:0%">0%</div>
        </div>
    </div>

    <div class="import-status" id="import_status" style="display:none;">
        <div class="upload-status__text" id="import_status_text">Імпорт у базу...</div>
        <div class="progress upload-progress">
            <div class="progress-bar progress-bar-striped active progress-bar-indeterminate" id="import_progress_bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width:100%"></div>
        </div>
    </div>

    <div class="table-responsive upload-preview" id="process_area"></div>
</div>
<?php
$this->registerCss(<<< CSS
.upload-hint,
.upload-status,
.import-status {
    margin-top: 15px;
}

.upload-status__text {
    font-weight: 600;
    margin-bottom: 8px;
}

.upload-preview {
    margin-top: 15px;
    background: #fff;
    border: 1px solid #e5e5e5;
    border-radius: 6px;
    padding: 15px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
}

.upload-preview table {
    margin-bottom: 0;
}

.upload-preview .table > thead > tr.active > th {
    background: #f5f5f5;
    font-size: 12px;
    vertical-align: middle;
}

.upload-preview .set_column_data {
    min-width: 150px;
}

.upload-progress {
    height: 22px;
    margin-bottom: 0;
}

.upload-progress .progress-bar {
    line-height: 22px;
    font-size: 12px;
}

.progress-bar-indeterminate {
    background-image: linear-gradient(45deg, rgba(255,255,255,.15) 25%, transparent 25%, transparent 50%, rgba(255,255,255,.15) 50%, rgba(255,255,255,.15) 75%, transparent 75%, transparent);
    background-size: 40px 40px;
    animation: progress-bar-stripes 1s linear infinite;
}

@keyframes progress-bar-stripes {
    from { background-position: 40px 0; }
    to { background-position: 0 0; }
}

.is-loading {
    opacity: .75;
    pointer-events: none;
}
CSS
);

$script = <<< JS
$(function() {
    var selectedColumns = {};
    var requiredFields = ['country', 'isrc', 'date_report', 'platform', 'count', 'amount'];
    var uploadProgressTimer = null;
    var importProgressTimer = null;

    function resetProgress(barSelector, textSelector) {
        $(barSelector).css('width', '0%').text('0%').attr('aria-valuenow', 0);
        if (textSelector) {
            $(textSelector).text('');
        }
    }

    function startFakeProgress(barSelector, percentStart, percentEnd, step, delay) {
        var progress = percentStart;
        var timerId = null;
        $(barSelector).css('width', progress + '%').attr('aria-valuenow', progress).text(progress + '%');

        timerId = setInterval(function() {
            progress = Math.min(percentEnd, progress + step);
            $(barSelector).css('width', progress + '%').attr('aria-valuenow', progress).text(progress + '%');

            if (progress >= percentEnd) {
                clearInterval(timerId);
            }
        }, delay);

        return timerId;
    }

    function rebuildSelectedColumns() {
        selectedColumns = {};

        $('.set_column_data').each(function() {
            var value = $(this).val();
            if (value) {
                selectedColumns[value] = parseInt($(this).data('column_number'), 10);
                $(this).data('selected_field', value);
            } else {
                $(this).data('selected_field', '');
            }
        });
    }

    function showUploadStatus(text) {
        $('#upload_status_text').text(text || 'Завантаження файлу...');
        $('#upload_status').show();
        $('#upload_area').addClass('is-loading');
        $('#upload_hint').hide();
    }

    function hideUploadStatus() {
        $('#upload_status').hide();
        $('#upload_area').removeClass('is-loading');
        if (uploadProgressTimer) {
            clearInterval(uploadProgressTimer);
            uploadProgressTimer = null;
        }
    }

    function showImportStatus(text) {
        $('#import_status_text').text(text || 'Імпорт у базу...');
        $('#import_status').show();
        $('#process_area').addClass('is-loading');
    }

    function hideImportStatus() {
        $('#import_status').hide();
        $('#process_area').removeClass('is-loading');
        if (importProgressTimer) {
            clearInterval(importProgressTimer);
            importProgressTimer = null;
        }
    }

    function syncSelectOptions() {
        var used = {};

        $('.set_column_data').each(function() {
            var value = $(this).val();
            if (value) {
                used[value] = true;
            }
        });

        $('.set_column_data').each(function() {
            var current = $(this).val();

            $(this).find('option').each(function() {
                var optionValue = $(this).val();

                if (!optionValue) {
                    return;
                }

                var disableOption = !!used[optionValue] && optionValue !== current;
                $(this).prop('disabled', disableOption);
            });
        });
    }

    function updateImportButtonState() {
        var hasRequired = requiredFields.every(function(field) {
            return Object.prototype.hasOwnProperty.call(selectedColumns, field);
        });

        $('#import').prop('disabled', !hasRequired);
    }

    $('#upload_file').on('beforeSubmit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);

        showUploadStatus('Завантаження файлу...');
        resetProgress('#upload_progress_bar');
        uploadProgressTimer = startFakeProgress('#upload_progress_bar', 3, 95, 7, 140);

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            dataType: 'html',
            contentType: false,
            processData: false,
            xhr: function() {
                var xhr = $.ajaxSettings.xhr();

                if (xhr.upload) {
                    xhr.upload.addEventListener('progress', function(event) {
                        if (event.lengthComputable) {
                            var percent = Math.round((event.loaded / event.total) * 100);
                            $('#upload_progress_bar').css('width', percent + '%').attr('aria-valuenow', percent).text(percent + '%');
                        }
                    }, false);
                }

                return xhr;
            },
            success: function(data) {
                if (uploadProgressTimer) {
                    clearInterval(uploadProgressTimer);
                }
                $('#upload_progress_bar').css('width', '100%').attr('aria-valuenow', 100).text('100%');
                selectedColumns = {};
                    if (importProgressTimer) {
                        clearInterval(importProgressTimer);
                    }
                    $('#import_progress_bar').css('width', '100%').attr('aria-valuenow', 100).text('100%');
                $('#process_area').html(data);
                $('#upload_area').hide();
                $('#upload_hint').hide();
                rebuildSelectedColumns();
                syncSelectOptions();
                updateImportButtonState();
                $('#process_area').show();
            },
            error: function(jqXHR) {
                hideUploadStatus();
                hideImportStatus();
                $('#message').html("<div class='alert alert-danger'>" + (jqXHR.responseText || 'Помилка завантаження файлу') + "</div>");
                $('#upload_hint').show();
            }
        });
    }).on('submit', function(e) {
        e.preventDefault();
    });

    $(document).on('change', '.set_column_data', function() {
        rebuildSelectedColumns();
        syncSelectOptions();
        updateImportButtonState();
    });

    $(document).on('click', '#import', function(event) {
        event.preventDefault();

        showImportStatus('Імпорт у базу...');
        resetProgress('#import_progress_bar');
        importProgressTimer = startFakeProgress('#import_progress_bar', 10, 90, 5, 180);

        $.ajax({
            url: '/aggregator/upload-import',
            method: 'POST',
            dataType: 'json',
            data: { columns: selectedColumns },
            beforeSend: function() {
                $('#import').prop('disabled', true).text('Importing...');
                $('#process_area').addClass('is-loading');
            },
            success: function(data) {
                hideImportStatus();
                if (data && data.success) {
                    $('#import_progress_bar').css('width', '100%').text('100%');
                    $('#message').html("<div class='alert alert-success'>" + data.message + "</div>");

                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                        return;
                    }
                } else {
                    $('#message').html("<div class='alert alert-danger'>" + (data.message || 'Помилка імпорту') + "</div>");
                }

                $('#import').prop('disabled', false).text('Import');
                $('#process_area').removeClass('is-loading');
            },
            error: function(jqXHR) {
                hideImportStatus();
                var msg = 'Помилка імпорту';
                if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    msg = jqXHR.responseJSON.message;
                }
                $('#message').html("<div class='alert alert-danger'>" + msg + "</div>");
                $('#import').prop('disabled', false).text('Import');
                $('#process_area').removeClass('is-loading');
            }
        });
    });
});
JS;
$this->registerJs($script);
