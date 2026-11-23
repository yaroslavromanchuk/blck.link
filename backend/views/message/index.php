<?php

use common\models\Lang;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\MessageSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Переклади');
$this->params['breadcrumbs'][] = $this->title;

$langItems = Lang::find()
    ->select(['name', 'id'])
    ->where(['active' => 1])
    ->orderBy(['name' => SORT_ASC])
    ->indexBy('id')
    ->column();
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;
?>
<div class="message-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <p><?= Html::a(Yii::t('app', 'Додати переклад'), ['create'], ['class' => 'btn btn-success']) ?></p>

    <?php Pjax::begin(['id' => 'message-pjax', 'timeout' => 10000]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'tableOptions' => ['class' => 'table table-striped table-bordered message-grid'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'id',
            [
                'attribute' => 'name',
                'format' => 'ntext',
                'contentOptions' => ['style' => 'min-width:260px'],
            ],
            [
                'attribute' => 'translate',
                'format' => 'raw',
                'contentOptions' => ['class' => 'message-translate-cell', 'style' => 'min-width:280px'],
                'value' => static function ($model) {
                    $text = (string)$model->translate;
                    $display = Html::encode($text !== '' ? $text : Yii::t('app', '— додати переклад —'));
                    return '<div class="message-inline" data-id="' . (int)$model->id . '">'
                        . '<div class="message-inline-display">'
                        . '<span class="message-inline-text' . ($text === '' ? ' text-muted' : '') . '">' . $display . '</span> '
                        . '<button type="button" class="btn btn-xs btn-default message-edit" title="' . Html::encode(Yii::t('app', 'Редагувати')) . '"><span class="glyphicon glyphicon-pencil"></span></button>'
                        . '</div>'
                        . '<div class="message-inline-editor" style="display:none">'
                        . '<textarea class="form-control message-inline-input" rows="3">' . Html::encode($text) . '</textarea>'
                        . '<div class="message-inline-error text-danger" style="display:none;margin-top:5px"></div>'
                        . '<div style="margin-top:7px">'
                        . '<button type="button" class="btn btn-primary btn-xs message-save"><span class="glyphicon glyphicon-ok"></span> ' . Html::encode(Yii::t('app', 'Зберегти')) . '</button> '
                        . '<button type="button" class="btn btn-default btn-xs message-cancel"><span class="glyphicon glyphicon-remove"></span> ' . Html::encode(Yii::t('app', 'Скасувати')) . '</button>'
                        . '</div>'
                        . '</div>'
                        . '</div>';
                },
            ],
            [
                'attribute' => 'lang_id',
                'filter' => $langItems,
                'value' => static fn($model) => $model->lang ? $model->lang->name : $model->lang_id,
                'contentOptions' => ['style' => 'white-space:nowrap'],
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'headerOptions' => ['style' => 'width:90px'],
            ],
        ],
    ]); ?>

    <?php Pjax::end(); ?>
</div>

<div id="message-inline-alert" class="alert" style="display:none;position:fixed;right:20px;top:70px;z-index:9999"></div>

<?php
$inlineUrl = json_encode(Url::to(['inline-update']), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$csrfParam = json_encode(Yii::$app->request->csrfParam, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$csrfToken = json_encode(Yii::$app->request->csrfToken, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$this->registerJs(<<<JS
(function () {
    var inlineUrl = {$inlineUrl};
    var csrfParam = {$csrfParam};
    var csrfToken = {$csrfToken};

    function alertResult(success, text) {
        var alert = $('#message-inline-alert');
        alert.removeClass('alert-success alert-danger')
            .addClass(success ? 'alert-success' : 'alert-danger')
            .text(text)
            .stop(true, true)
            .fadeIn(150);
        window.setTimeout(function () { alert.fadeOut(250); }, 2500);
    }

    $(document).on('click', '.message-edit', function () {
        var root = $(this).closest('.message-inline');
        root.find('.message-inline-display').hide();
        root.find('.message-inline-editor').show();
        root.find('.message-inline-input').trigger('focus').trigger('select');
    });

    $(document).on('click', '.message-cancel', function () {
        var root = $(this).closest('.message-inline');
        var original = root.find('.message-inline-text').text();
        if (original === '— додати переклад —') {
            original = '';
        }
        root.find('.message-inline-input').val(original);
        root.find('.message-inline-error').hide().empty();
        root.find('.message-inline-editor').hide();
        root.find('.message-inline-display').show();
    });

    $(document).on('keydown', '.message-inline-input', function (event) {
        if (event.key === 'Escape') {
            $(this).closest('.message-inline').find('.message-cancel').trigger('click');
        }
        if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
            $(this).closest('.message-inline').find('.message-save').trigger('click');
        }
    });

    $(document).on('click', '.message-save', function () {
        var button = $(this);
        var root = button.closest('.message-inline');
        var input = root.find('.message-inline-input');
        var error = root.find('.message-inline-error');

        if (button.data('loading')) return;
        button.data('loading', true).prop('disabled', true);

        var data = {
            id: root.data('id'),
            translate: input.val()
        };
        data[csrfParam] = csrfToken;

        $.ajax({
            url: inlineUrl,
            method: 'POST',
            dataType: 'json',
            data: data
        }).done(function (response) {
            if (!response.success) {
                var message = response.errors && response.errors.translate
                    ? response.errors.translate
                    : 'Не вдалося зберегти переклад.';
                error.text(message).show();
                return;
            }

            var text = response.translate;
            var display = text !== '' ? $('<div>').text(text).html() : '— додати переклад —';
            root.find('.message-inline-text').html(display);
            root.find('.message-inline-text').toggleClass('text-muted', text === '');
            root.find('.message-inline-editor').hide();
            root.find('.message-inline-display').show();
            error.hide().empty();
            alertResult(true, 'Переклад збережено');
        }).fail(function (xhr) {
            var message = xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message
                : 'Не вдалося зберегти переклад.';
            error.text(message).show();
            alertResult(false, message);
        }).always(function () {
            button.data('loading', false).prop('disabled', false);
        });
    });

    $(document).on('click', '.message-inline-text', function () {
        $(this).siblings('.message-edit').trigger('click');
    });
})();
JS
, yii\web\View::POS_END);
?>
