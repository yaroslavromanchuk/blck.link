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
    <div class="upload-dashboard">
        <div class="upload-dashboard__header">
            <div>
                <h3 class="mt-0 mb-5">Завантаження звіту</h3>
                <p class="text-muted mb-0">Підготовка файлу, зіставлення колонок та контроль якості імпорту.</p>
            </div>
        </div>

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
                        <?= Html::submitButton(Yii::t('app', 'Завантажити'), ['class' => 'btn btn-success btn-block']) ?>
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

        <div class="upload-dashboard__summary" id="upload_summary" style="display:none;">
            <div class="upload-summary__card upload-summary__card--success">
                <span class="upload-summary__label">Статус</span>
                <strong class="upload-summary__value" id="summary_status">Готово</strong>
            </div>
            <div class="upload-summary__card upload-summary__card--warning">
                <span class="upload-summary__label">Критичні</span>
                <strong class="upload-summary__value" id="summary_critical">0</strong>
            </div>
            <div class="upload-summary__card upload-summary__card--muted">
                <span class="upload-summary__label">Не критичні</span>
                <strong class="upload-summary__value" id="summary_noncritical">0</strong>
            </div>
        </div>

        <div class="upload-warning-panel" id="warning_panel" style="display:none;"></div>
    </div>

    <div class="table-responsive upload-preview" id="process_area"></div>
</div>
<?php
$this->registerCss(<<< CSS
.upload-dashboard {
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
}

.upload-dashboard__header {
    margin-bottom: 20px;
}

.upload-dashboard__summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(180px, 1fr));
    gap: 14px;
    margin: 18px 0;
}

.upload-summary__card {
    border-radius: 12px;
    padding: 16px 18px;
    border: 1px solid #e5e7eb;
    background: #fff;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.upload-summary__card--success {
    border-left: 4px solid #1f9d55;
}

.upload-summary__card--warning {
    border-left: 4px solid #f0ad4e;
}

.upload-summary__card--muted {
    border-left: 4px solid #7d7d7d;
}

.upload-summary__label {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #6b7280;
}

.upload-summary__value {
    font-size: 28px;
    line-height: 1.2;
    color: #111827;
    font-weight: 700;
}

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
    border-radius: 12px;
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

.upload-warning-panel {
    margin-top: 24px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.04);
}

.warning-panel__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 16px;
}

.warning-panel__filters {
    display: inline-flex;
    gap: 8px;
    flex-wrap: wrap;
}

.warning-filter {
    min-width: 120px;
}

.warning-group {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    background: #f8fafc;
    overflow: hidden;
    margin-bottom: 12px;
}

.warning-group__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 14px;
    border-bottom: 1px solid #edf2f7;
    background: #f3f4f6;
}

.warning-group__title {
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.warning-group__count {
    min-width: 28px;
    display: inline-flex;
    justify-content: center;
    align-items: center;
    padding: 4px 8px;
    border-radius: 999px;
    background: #fff;
    font-weight: 700;
    font-size: 12px;
}

.warning-group__list {
    margin: 0;
    padding: 12px 16px 12px 34px;
    list-style: disc;
    line-height: 1.6;
}

.warning-group--critical .warning-group__title {
    color: #a16207;
}

.warning-group--critical .warning-group__count {
    color: #a16207;
    background: #fff7ed;
}

.warning-group--noncritical .warning-group__title {
    color: #4b5563;
}

.warning-group--noncritical .warning-group__count {
    color: #374151;
    background: #f3f4f6;
}

.warning-empty {
    color: #6b7280;
    margin: 0;
    padding: 12px 0 0;
}

@keyframes progress-bar-stripes {
    from { background-position: 40px 0; }
    to { background-position: 0 0; }
}

.is-loading {
    opacity: .75;
    pointer-events: none;
}

@media (max-width: 768px) {
    .upload-dashboard__summary {
        grid-template-columns: 1fr;
    }

    .warning-panel__header {
        align-items: flex-start;
        flex-direction: column;
    }
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

    function clearWarningPanel() {
        $('#warning_panel').hide().empty();
        $('#upload_summary').hide();
    }

    function updateSummary(statusText, criticalCount, nonCriticalCount) {
        $('#summary_status').text(statusText || 'Готово');
        $('#summary_critical').text(criticalCount || 0);
        $('#summary_noncritical').text(nonCriticalCount || 0);
        $('#upload_summary').show();
    }

    function buildWarningSection(title, items, severityKey) {
        var sectionClass = severityKey === 'critical' ? 'warning-group--critical' : 'warning-group--noncritical';
        var listHtml = '';

        if (!items || !items.length) {
            listHtml = '<p class="warning-empty">Немає записів.</p>';
        } else {
            listHtml = '<ul class="warning-group__list">';
            $.each(items, function(index, item) {
                listHtml += '<li>' + item + '</li>';
            });
            listHtml += '</ul>';
        }

        return '<div class="warning-group ' + sectionClass + '" data-warning-section="' + severityKey + '">' +
            '<div class="warning-group__header">' +
                '<span class="warning-group__title">' + title + '</span>' +
                '<span class="warning-group__count">' + (items ? items.length : 0) + '</span>' +
            '</div>' +
            listHtml +
        '</div>';
    }

    function renderWarningPanel(data) {
        var critical = Array.isArray(data && data.critical_warnings) ? data.critical_warnings : [];
        var nonCritical = Array.isArray(data && data.non_critical_warnings) ? data.non_critical_warnings : [];
        var totalWarnings = critical.length + nonCritical.length;

        if (!totalWarnings) {
            $('#warning_panel').hide().empty();
            $('#message').append("<div class='alert alert-info' style='margin-top:15px'><strong>Увага:</strong> Імпорт завершено, але всі ISRC знайдено в системі.</div>");
            updateSummary('Без помилок', 0, 0);
            $('#upload_summary').show();
            return;
        }

        updateSummary('З попередженнями', critical.length, nonCritical.length);

        var html = '<div class="warning-panel__header">' +
            '<h4 class="mt-0 mb-0">Контроль якості імпорту</h4>' +
            '<div class="warning-panel__filters">' +
                '<button type="button" class="btn btn-sm btn-warning warning-filter active" data-filter="all">Усі попередження</button>' +
                '<button type="button" class="btn btn-sm btn-default warning-filter" data-filter="critical">Критичні</button>' +
                '<button type="button" class="btn btn-sm btn-default warning-filter" data-filter="noncritical">Не критичні</button>' +
            '</div>' +
        '</div>' +
        '<div class="warning-list">' +
            buildWarningSection('Критичні', critical, 'critical') +
            buildWarningSection('Не критичні', nonCritical, 'noncritical') +
        '</div>';

        $('#warning_panel').html(html).show();

        $('.warning-filter').off('click').on('click', function() {
            var filter = $(this).data('filter');
            $('.warning-filter').removeClass('btn-warning').addClass('btn-default');
            $(this).removeClass('btn-default').addClass('btn-warning');

            $('.warning-group').each(function() {
                var section = $(this).data('warning-section');
                var shouldShow = filter === 'all' || section === filter;
                $(this).toggle(shouldShow);
            });
        });
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

    function clearMessage() {
        $('#message').empty();
    }

    function setMessage(type, text) {
        $('#message').html("<div class='alert alert-" + type + "' style='margin-top:15px'>" + text + "</div>");
    }

    $('#upload_file').on('beforeSubmit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);

        clearWarningPanel();
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

        clearMessage();
        clearWarningPanel();
        showImportStatus('Імпорт у базу...');
        resetProgress('#import_progress_bar');
        importProgressTimer = startFakeProgress('#import_progress_bar', 10, 90, 5, 180);

        $.ajax({
            url: '/aggregator/upload-import',
            method: 'POST',
            dataType: 'json',
            data: { columns: selectedColumns },
            beforeSend: function() {
                $('#import').prop('disabled', true).text('Імпорт...');
                $('#process_area').addClass('is-loading');
            },
            success: function(data) {
                hideImportStatus();
                if (data && data.success) {
                    $('#import_progress_bar').css('width', '100%').text('100%');
                    setMessage('success', data.message || 'Імпорт завершено успішно.');

                    if (data.warning_message) {
                        $('#message').append("<div class='alert alert-warning' style='margin-top:15px'><strong>" + data.warning_message + "</strong></div>");
                    }

                    renderWarningPanel(data);

                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                        return;
                    }
                } else {
                    setMessage('danger', (data && data.message) || 'Помилка імпорту');
                }

                $('#import').prop('disabled', false).text('Імпортувати звіт');
                $('#process_area').removeClass('is-loading');
            },
            error: function(jqXHR) {
                hideImportStatus();
                var msg = 'Помилка імпорту';
                if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    msg = jqXHR.responseJSON.message;
                }
                setMessage('danger', msg);
                $('#import').prop('disabled', false).text('Імпортувати звіт');
                $('#process_area').removeClass('is-loading');
            }
        });
    });
});
JS;
$this->registerJs($script);

