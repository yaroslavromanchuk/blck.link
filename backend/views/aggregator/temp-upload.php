<?php

/** @var $count_header int */
/** @var $file_header array */
/** @var $file_data array */

$requiredFields = [
    'country' => 'Країна',
    'date_report' => 'Місяць звіту',
    'platform' => 'Платформа',
    'isrc' => 'ISRC',
    'count' => 'Кількість переглядів',
    'amount' => 'Сума',
];
?>

<div class="upload-mapping-shell crm-dark">
    <div class="upload-mapping-header">
        <div>
            <span class="upload-step-badge">Крок 2</span>
            <h4 class="mt-5 mb-5">Зіставлення колонок</h4>
            <p class="text-muted mb-0">Виберіть поля для імпорту та перевірте, що файл підготовлений до завантаження.</p>
        </div>

        <div class="upload-mapping-actions">
            <div class="upload-mapping-corner-metrics" aria-label="Метрики імпорту">
                <div class="metric-badge metric-badge--success">
                    <span class="metric-badge__label">Ready</span>
                    <strong class="metric-badge__value" id="metric_ready">0</strong>
                </div>
                <div class="metric-badge metric-badge--info">
                    <span class="metric-badge__label">Mapped</span>
                    <strong class="metric-badge__value" id="metric_mapped">0</strong>
                </div>
                <div class="metric-badge metric-badge--neutral">
                    <span class="metric-badge__label">Rows</span>
                    <strong class="metric-badge__value"><?= count($file_data) ?></strong>
                </div>
            </div>
            <span class="upload-mapping-status" id="mapping_status">Ожидає вибору полів</span>
            <button type="button" name="import" id="import" class="btn btn-success" disabled>
                Імпортувати звіт
            </button>
        </div>
    </div>

    <div class="upload-mapping-progress-wrap">
        <div class="upload-mapping-progress-meta">
            <span>Заповнення обов’язкових полів</span>
            <strong id="mapping_progress_text">0 / <?= count($requiredFields) ?></strong>
        </div>
        <div class="progress upload-mapping-progress">
            <div id="mapping_progress_bar" class="progress-bar progress-bar-success" role="progressbar" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>
        </div>
    </div>

    <div class="upload-mapping-grid">
        <aside class="upload-mapping-sidebar">
            <div class="panel-block">
                <h5>Обов’язкові поля</h5>
                <ul class="mapping-required-list">
                    <?php foreach ($requiredFields as $field => $label) : ?>
                        <li class="mapping-required-item" data-field="<?= htmlspecialchars($field, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                            <span><?= htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                            <span class="mapping-required-state">Не обрано</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="panel-block panel-block--muted">
                <h5>Файл</h5>
                <div class="mapping-file-stats">
                    <div>
                        <small>Колонок</small>
                        <strong><?= (int)$count_header ?></strong>
                    </div>
                    <div>
                        <small>Попередній перегляд</small>
                        <strong><?= count($file_data) ?> рядків</strong>
                    </div>
                </div>
            </div>
        </aside>

        <div class="upload-mapping-main">
            <div class="upload-mapping-summary">
                <div class="summary-card summary-card--green">
                    <span class="summary-card__label">Файл</span>
                    <strong><?= (int)$count_header ?> колонок</strong>
                </div>
                <div class="summary-card summary-card--blue">
                    <span class="summary-card__label">Рядків для перевірки</span>
                    <strong><?= count($file_data) ?> зразків</strong>
                </div>
                <div class="summary-card summary-card--amber">
                    <span class="summary-card__label">Вибрано полів</span>
                    <strong id="selected_field_counter">0 / <?= count($requiredFields) ?></strong>
                </div>
            </div>

            <div class="table-responsive upload-preview-table-wrap">
                <table class="table table-bordered table-condensed upload-preview-table">
                    <thead>
                    <tr class="active">
                        <?php for ($count = 0; $count < $count_header; $count++) { ?>
                            <th class="column-header-cell" data-column-index="<?= $count ?>"><?= htmlspecialchars((string)($file_header[$count] ?? ('Колонка ' . ($count + 1))), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></th>
                        <?php } ?>
                    </tr>
                    <tr class="mapping-select-row">
                        <?php for ($count = 0; $count < $count_header; $count++) { ?>
                            <th class="mapping-select-cell" data-column-index="<?= $count ?>">
                                <label class="sr-only" for="set_column_<?= $count ?>">Вкажіть назву колонки <?= $count + 1 ?></label>
                                <select id="set_column_<?= $count ?>" name="set_column_data" class="form-control set_column_data" data-column_number="<?= $count ?>">
                                    <option value="">Оберіть поле</option>
                                    <?php foreach ($requiredFields as $field => $label) { ?>
                                        <option value="<?= htmlspecialchars($field, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></option>
                                    <?php } ?>
                                </select>
                            </th>
                        <?php } ?>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($file_data as $row) { ?>
                        <tr>
                            <?php foreach ($row as $value) { ?>
                                <td><?= htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .upload-mapping-shell {
        position: relative;
    }

    .upload-mapping-shell::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(120deg, rgba(59,130,246,0.12), transparent 35%, rgba(45,212,191,0.08));
        pointer-events: none;
        border-radius: inherit;
    }

    .crm-dark {
        background: radial-gradient(circle at top left, rgba(56, 189, 248, 0.18), transparent 22%),
                    radial-gradient(circle at top right, rgba(34, 197, 94, 0.14), transparent 20%),
                    linear-gradient(180deg, #0b1020 0%, #111827 100%);
        border-color: rgba(96, 165, 250, 0.18);
        box-shadow: 0 20px 44px rgba(2, 6, 23, 0.55);
    }

    .crm-dark .upload-mapping-header h4,
    .crm-dark .summary-card strong,
    .crm-dark .mapping-required-item,
    .crm-dark .upload-mapping-status,
    .crm-dark .upload-mapping-progress-meta,
    .crm-dark .panel-block h5,
    .crm-dark .upload-mapping-header p,
    .crm-dark .mapping-required-state,
    .crm-dark .summary-card__label,
    .crm-dark .upload-mapping-status,
    .crm-dark .upload-step-badge {
        color: #e2e8f0;
    }

    .crm-dark .upload-mapping-header p,
    .crm-dark .mapping-required-state,
    .crm-dark .summary-card__label,
    .crm-dark .upload-mapping-progress-meta,
    .crm-dark .panel-block h5 {
        opacity: 0.9;
    }

    .crm-dark .upload-step-badge {
        background: linear-gradient(135deg, rgba(56, 189, 248, 0.18), rgba(14, 165, 233, 0.08));
        border: 1px solid rgba(125, 211, 252, 0.2);
        color: #bae6fd;
        box-shadow: 0 0 24px rgba(14, 165, 233, 0.12);
    }

    .crm-dark .upload-mapping-status {
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.75) 0%, rgba(30, 41, 59, 0.9) 100%);
        border-color: rgba(148, 163, 184, 0.22);
        box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.08);
    }

    .crm-dark .upload-mapping-progress-wrap,
    .crm-dark .panel-block,
    .crm-dark .summary-card,
    .crm-dark .upload-preview-table-wrap,
    .crm-dark .upload-preview-table thead tr.active th,
    .crm-dark .mapping-select-row th,
    .crm-dark .mapping-required-item,
    .crm-dark .mapping-file-stats > div,
    .crm-dark .metric-badge {
        background: rgba(15, 23, 42, 0.72);
        border-color: rgba(148, 163, 184, 0.18);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.03);
    }

    .crm-dark .upload-mapping-progress {
        background: rgba(51, 65, 85, 0.9);
    }

    .crm-dark .mapping-required-item {
        color: #e2e8f0;
    }

    .crm-dark .mapping-required-item.is-selected {
        background: linear-gradient(180deg, rgba(16, 185, 129, 0.18), rgba(6, 78, 59, 0.18));
        border-color: rgba(52, 211, 153, 0.5);
        box-shadow: 0 0 0 1px rgba(52, 211, 153, 0.08), 0 0 22px rgba(16, 185, 129, 0.12);
    }

    .crm-dark .upload-preview-table .set_column_data {
        background: rgba(15, 23, 42, 0.9);
        border-color: rgba(148, 163, 184, 0.32);
        color: #e5eefb;
        box-shadow: inset 0 0 0 1px rgba(96, 165, 250, 0.08);
    }

    .crm-dark .upload-preview-table .set_column_data:focus {
        border-color: rgba(96, 165, 250, 0.8);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18);
    }

    .crm-dark .upload-preview-table-wrap {
        background: rgba(15, 23, 42, 0.58);
    }

    .crm-dark .column-header-cell {
        color: #cbd5e1;
    }

    .crm-dark .column-header-cell.is-linked {
        background: linear-gradient(180deg, rgba(34, 197, 94, 0.18), rgba(15, 23, 42, 0.8)) !important;
        color: #d1fae5 !important;
        box-shadow: inset 0 0 0 1px rgba(52, 211, 153, 0.2), 0 0 16px rgba(52, 211, 153, 0.08);
    }

    .crm-dark .mapping-select-cell.is-active {
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.12), rgba(15, 23, 42, 0.76));
        box-shadow: inset 0 0 0 1px rgba(96, 165, 250, 0.14);
    }

    .crm-dark .metric-badge {
        border-radius: 12px;
        padding: 8px 10px;
        min-width: 72px;
        border: 1px solid rgba(148, 163, 184, 0.2);
    }

    .crm-dark .metric-badge__label {
        display: block;
        font-size: 10px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #a5b4cf;
    }

    .crm-dark .metric-badge__value {
        display: block;
        font-size: 18px;
        line-height: 1.2;
        color: #e2e8f0;
        font-weight: 800;
    }

    .crm-dark .metric-badge--success {
        box-shadow: inset 0 0 0 1px rgba(34, 197, 94, 0.15), 0 0 16px rgba(34, 197, 94, 0.08);
    }

    .crm-dark .metric-badge--info {
        box-shadow: inset 0 0 0 1px rgba(59, 130, 246, 0.15), 0 0 16px rgba(59, 130, 246, 0.08);
    }

    .crm-dark .metric-badge--neutral {
        box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.15), 0 0 16px rgba(148, 163, 184, 0.08);
    }

    .crm-dark .upload-mapping-corner-metrics {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-right: 8px;
    }

    .crm-dark .upload-mapping-shell {
        background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 18px;
        padding: 22px;
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.06);
    }

    .crm-dark .upload-mapping-header h4,
    .crm-dark .summary-card strong,
    .crm-dark .mapping-required-item,
    .crm-dark .upload-mapping-status,
    .crm-dark .upload-mapping-progress-meta,
    .crm-dark .panel-block h5,
    .crm-dark .upload-mapping-header p,
    .crm-dark .mapping-required-state,
    .crm-dark .summary-card__label,
    .crm-dark .upload-mapping-status,
    .crm-dark .upload-step-badge {
        color: #e2e8f0;
    }

    .crm-dark .upload-mapping-header p,
    .crm-dark .mapping-required-state,
    .crm-dark .summary-card__label,
    .crm-dark .upload-mapping-progress-meta,
    .crm-dark .panel-block h5 {
        opacity: 0.9;
    }

    .crm-dark .upload-step-badge {
        background: linear-gradient(135deg, rgba(56, 189, 248, 0.18), rgba(14, 165, 233, 0.08));
        border: 1px solid rgba(125, 211, 252, 0.2);
        color: #bae6fd;
        box-shadow: 0 0 24px rgba(14, 165, 233, 0.12);
    }

    .crm-dark .upload-mapping-status {
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.75) 0%, rgba(30, 41, 59, 0.9) 100%);
        border-color: rgba(148, 163, 184, 0.22);
        box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.08);
    }

    .crm-dark .upload-mapping-progress-wrap,
    .crm-dark .panel-block,
    .crm-dark .summary-card,
    .crm-dark .upload-preview-table-wrap,
    .crm-dark .upload-preview-table thead tr.active th,
    .crm-dark .mapping-select-row th,
    .crm-dark .mapping-required-item,
    .crm-dark .mapping-file-stats > div,
    .crm-dark .metric-badge {
        background: rgba(15, 23, 42, 0.72);
        border-color: rgba(148, 163, 184, 0.18);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.03);
    }

    .crm-dark .upload-mapping-progress {
        background: rgba(51, 65, 85, 0.9);
    }

    .crm-dark .mapping-required-item {
        color: #e2e8f0;
    }

    .crm-dark .mapping-required-item.is-selected {
        background: linear-gradient(180deg, rgba(16, 185, 129, 0.18), rgba(6, 78, 59, 0.18));
        border-color: rgba(52, 211, 153, 0.5);
        box-shadow: 0 0 0 1px rgba(52, 211, 153, 0.08), 0 0 22px rgba(16, 185, 129, 0.12);
    }

    .crm-dark .upload-preview-table .set_column_data {
        background: rgba(15, 23, 42, 0.9);
        border-color: rgba(148, 163, 184, 0.32);
        color: #e5eefb;
        box-shadow: inset 0 0 0 1px rgba(96, 165, 250, 0.08);
    }

    .crm-dark .upload-preview-table .set_column_data:focus {
        border-color: rgba(96, 165, 250, 0.8);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18);
    }

    .crm-dark .upload-preview-table-wrap {
        background: rgba(15, 23, 42, 0.58);
    }

    .crm-dark .column-header-cell {
        color: #cbd5e1;
    }

    .crm-dark .column-header-cell.is-linked {
        background: linear-gradient(180deg, rgba(34, 197, 94, 0.18), rgba(15, 23, 42, 0.8)) !important;
        color: #d1fae5 !important;
        box-shadow: inset 0 0 0 1px rgba(52, 211, 153, 0.2), 0 0 16px rgba(52, 211, 153, 0.08);
    }

    .crm-dark .mapping-select-cell.is-active {
        background: linear-gradient(180deg, rgba(59, 130, 246, 0.12), rgba(15, 23, 42, 0.76));
        box-shadow: inset 0 0 0 1px rgba(96, 165, 250, 0.14);
    }

    .crm-dark .metric-badge {
        border-radius: 12px;
        padding: 8px 10px;
        min-width: 72px;
        border: 1px solid rgba(148, 163, 184, 0.2);
    }

    .crm-dark .metric-badge__label {
        display: block;
        font-size: 10px;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #a5b4cf;
    }

    .crm-dark .metric-badge__value {
        display: block;
        font-size: 18px;
        line-height: 1.2;
        color: #e2e8f0;
        font-weight: 800;
    }

    .crm-dark .metric-badge--success {
        box-shadow: inset 0 0 0 1px rgba(34, 197, 94, 0.15), 0 0 16px rgba(34, 197, 94, 0.08);
    }

    .crm-dark .metric-badge--info {
        box-shadow: inset 0 0 0 1px rgba(59, 130, 246, 0.15), 0 0 16px rgba(59, 130, 246, 0.08);
    }

    .crm-dark .metric-badge--neutral {
        box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.15), 0 0 16px rgba(148, 163, 184, 0.08);
    }

    .crm-dark .upload-mapping-corner-metrics {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-right: 8px;
    }

    .crm-dark .upload-mapping-shell {
        position: relative;
    }

    .crm-dark .upload-mapping-shell::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(120deg, rgba(59,130,246,0.12), transparent 35%, rgba(45,212,191,0.08));
        pointer-events: none;
        border-radius: inherit;
    }

    .crm-dark .upload-mapping-header,
    .crm-dark .upload-mapping-progress-wrap,
    .crm-dark .upload-mapping-grid,
    .crm-dark .upload-mapping-main,
    .crm-dark .upload-preview-table-wrap {
        position: relative;
        z-index: 1;
    }
</style>

<script>
    (function () {
        function updateColumnHighlights() {
            var linked = {};

            $('.set_column_data').each(function () {
                var value = $(this).val();
                var columnIndex = $(this).data('column_number');
                var header = $('.column-header-cell[data-column-index="' + columnIndex + '"]');
                var cell = $('.mapping-select-cell[data-column-index="' + columnIndex + '"]');

                if (value) {
                    linked[columnIndex] = value;
                    header.addClass('is-linked');
                    cell.addClass('is-active');
                } else {
                    header.removeClass('is-linked');
                    cell.removeClass('is-active');
                }
            });

            $('.set_column_data').each(function () {
                var value = $(this).val();
                var columnIndex = $(this).data('column_number');
                var header = $('.column-header-cell[data-column-index="' + columnIndex + '"]');
                var cell = $('.mapping-select-cell[data-column-index="' + columnIndex + '"]');

                if (!value) {
                    header.removeClass('is-linked');
                    cell.removeClass('is-active');
                }
            });
        }

        function animateCounter(target, elementId) {
            var $el = $(elementId);
            var value = parseInt($el.text().split('/')[0], 10) || 0;
            var step = Math.max(1, Math.ceil((target - value) / 10));

            function tick() {
                value += step;
                if ((step > 0 && value >= target) || (step < 0 && value <= target)) {
                    value = target;
                }
                $el.text(value + ' / ' + requiredFieldsCount);
                if (value !== target) {
                    requestAnimationFrame(tick);
                }
            }

            requestAnimationFrame(tick);
        }

        function updateMappingProgress() {
            var requiredFields = ['country', 'date_report', 'platform', 'isrc', 'count', 'amount'];
            var requiredFieldsCount = requiredFields.length;
            var chosen = {};
            var count = 0;

            $('.set_column_data').each(function () {
                var value = $(this).val();
                if (value) {
                    chosen[value] = true;
                }
            });

            $.each(requiredFields, function (_, field) {
                var item = $('.mapping-required-item[data-field="' + field + '"]');
                if (chosen[field]) {
                    item.addClass('is-selected');
                    item.find('.mapping-required-state').text('Обрано');
                    count++;
                } else {
                    item.removeClass('is-selected');
                    item.find('.mapping-required-state').text('Не обрано');
                }
            });

            var percent = Math.round((count / requiredFieldsCount) * 100);
            $('#mapping_progress_bar').css('width', percent + '%').attr('aria-valuenow', percent);
            $('#mapping_progress_text').text(count + ' / ' + requiredFieldsCount);
            $('#selected_field_counter').text(count + ' / ' + requiredFieldsCount);
            $('#mapping_status').text(count === requiredFieldsCount ? 'Готово до імпорту' : 'Ожидає вибору полів');

            var ready = count === requiredFieldsCount;
            $('#import').prop('disabled', !ready);
            updateColumnHighlights();
        }

        $(document).on('change', '.set_column_data', function () {
            updateMappingProgress();
        });

        $(function () {
            updateMappingProgress();
        });
    })();
</script>
