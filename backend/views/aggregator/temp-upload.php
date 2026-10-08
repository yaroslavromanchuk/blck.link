<?php

/** @var $count_header int */
/** @var $file_header array */
/** @var $file_data array */
/** @var $aggregator_id int|null */

$requiredFields = [
    'country' => 'Країна',
    'date_report' => 'Місяць звіту',
    'platform' => 'Платформа',
    'isrc' => 'ISRC',
    'count' => 'Кількість переглядів',
    'amount' => 'Сума',
];
$requiredCount = count($requiredFields);
$aggregatorId = isset($aggregator_id) ? (int)$aggregator_id : 0;
?>

<div class="upload-mapping-shell" data-required-count="<?= (int)$requiredCount ?>" data-aggregator-id="<?= $aggregatorId ?>">
    <div class="upload-mapping-header">
        <div>
            <span class="upload-step-badge">Крок 2</span>
            <h4 class="mt-5 mb-5">Зіставлення колонок</h4>
            <p class="text-muted mb-0">Оберіть відповідність колонок. Кнопка імпорту активується тільки після вибору всіх обов’язкових полів.</p>
        </div>

        <div class="upload-mapping-actions">
            <div class="metric-box">
                <small>Вибрано полів</small>
                <strong id="selected_field_counter">0 / <?= (int)$requiredCount ?></strong>
            </div>
            <div class="metric-box">
                <small>Колонок у файлі</small>
                <strong><?= (int)$count_header ?></strong>
            </div>
            <div class="metric-box">
                <small>Рядків прев’ю</small>
                <strong><?= count($file_data) ?></strong>
            </div>
            <span class="upload-mapping-status" id="mapping_status">Очікує вибору полів</span>
            <button type="button" id="mapping_reset" class="btn btn-default">
                Скинути мапінг
            </button>
            <button type="button" id="mapping_toggle_compact" class="btn btn-default" aria-pressed="false">
                Компактний режим
            </button>
            <button type="button" name="import" id="import" class="btn btn-success" disabled>
                Імпортувати звіт
            </button>
        </div>
    </div>

    <div class="upload-mapping-progress-wrap">
        <div class="upload-mapping-progress-meta">
            <span>Заповнення обов’язкових полів</span>
            <strong id="mapping_progress_text">0 / <?= (int)$requiredCount ?></strong>
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
        </aside>

        <div class="upload-mapping-main">
            <div class="table-responsive upload-preview-table-wrap">
                <table class="table table-bordered table-condensed upload-preview-table">
                    <thead>
                    <tr class="active">
                        <?php for ($count = 0; $count < $count_header; $count++) { ?>
                            <th class="column-header-cell" data-column-index="<?= $count ?>">
                                <?= htmlspecialchars((string)($file_header[$count] ?? ('Колонка ' . ($count + 1))), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                            </th>
                        <?php } ?>
                    </tr>
                    <tr class="mapping-select-row">
                        <?php for ($count = 0; $count < $count_header; $count++) { ?>
                            <th class="mapping-select-cell" data-column-index="<?= $count ?>">
                                <label class="sr-only" for="set_column_<?= $count ?>">Вкажіть назву колонки <?= $count + 1 ?></label>
                                <select id="set_column_<?= $count ?>" name="set_column_data" class="form-control set_column_data" data-column_number="<?= $count ?>">
                                    <option value="">Оберіть поле</option>
                                    <?php foreach ($requiredFields as $field => $label) { ?>
                                        <option value="<?= htmlspecialchars($field, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                                            <?= htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                        </option>
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
        background: #ffffff;
        border: 1px solid #dbe2ea;
        border-radius: 12px;
        padding: 18px;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);
    }

    .upload-mapping-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 16px;
        padding-bottom: 14px;
        border-bottom: 1px solid #e9eef4;
    }

    .upload-step-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 999px;
        background: #eaf3ff;
        color: #1d4f91;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .upload-mapping-header h4 {
        font-size: 22px;
        font-weight: 700;
        color: #18263a;
    }

    .upload-mapping-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .metric-box {
        min-width: 112px;
        background: #f8fbff;
        border: 1px solid #dbe8f8;
        border-radius: 10px;
        padding: 6px 10px;
    }

    .metric-box small {
        display: block;
        font-size: 10px;
        text-transform: uppercase;
        color: #5d6f84;
        letter-spacing: 0.06em;
    }

    .metric-box strong {
        display: block;
        margin-top: 2px;
        color: #18263a;
        font-size: 18px;
        line-height: 1.2;
        font-weight: 700;
    }

    #selected_field_counter.counter-animate {
        animation: metricPulse 0.35s ease;
    }

    @keyframes metricPulse {
        0% { transform: scale(1); }
        45% { transform: scale(1.08); }
        100% { transform: scale(1); }
    }

    .upload-mapping-status {
        display: inline-flex;
        align-items: center;
        min-height: 34px;
        padding: 6px 10px;
        border-radius: 8px;
        background: #f4f7fb;
        color: #4c6079;
        border: 1px solid #dde6f1;
        font-size: 12px;
        font-weight: 600;
    }

    .upload-mapping-status.is-ready {
        background: #eefbf4;
        color: #1f7a43;
        border-color: #bde8cf;
    }

    .upload-mapping-progress-wrap {
        border: 1px solid #e6edf5;
        border-radius: 10px;
        padding: 10px 12px;
        margin-bottom: 16px;
        background: #fafcfe;
    }

    .upload-mapping-progress-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
        color: #3f536d;
        font-weight: 600;
        margin-bottom: 7px;
    }

    .upload-mapping-progress {
        height: 10px;
        border-radius: 999px;
        background: #e8eef6;
    }

    .upload-mapping-progress .progress-bar {
        border-radius: 999px;
        transition: width 0.2s ease;
    }

    .upload-mapping-grid {
        display: grid;
        grid-template-columns: 290px minmax(0, 1fr);
        gap: 16px;
    }

    .panel-block {
        background: #f9fbfd;
        border: 1px solid #e5edf5;
        border-radius: 10px;
        padding: 12px;
    }

    .panel-block h5 {
        margin: 0 0 10px;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #5d7087;
        font-weight: 700;
    }

    .mapping-required-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: grid;
        gap: 8px;
    }

    .mapping-required-item {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        border: 1px solid #e2eaf4;
        border-radius: 8px;
        background: #fff;
        padding: 8px 10px;
        color: #31455f;
        font-size: 13px;
    }

    .mapping-required-state {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #7b8da3;
        font-weight: 700;
    }

    .mapping-required-item.is-selected {
        border-color: #bfe4ce;
        background: #f2fcf6;
    }

    .mapping-required-item.is-selected .mapping-required-state {
        color: #1f7a43;
    }

    .upload-preview-table-wrap {
        border: 1px solid #dfe7f1;
        border-radius: 10px;
        overflow: auto;
        max-height: 520px;
    }

    .upload-preview-table {
        margin-bottom: 0;
    }

    .upload-preview-table thead tr.active th {
        position: sticky;
        top: 0;
        z-index: 3;
        background: #eef4fb;
        color: #30475f;
        font-size: 12px;
        font-weight: 700;
        vertical-align: middle;
        white-space: nowrap;
    }

    .upload-preview-table .mapping-select-row th {
        position: sticky;
        top: 40px;
        z-index: 2;
        background: #f8fbff;
        transition: background-color 0.2s ease, box-shadow 0.2s ease;
    }

    .upload-preview-table .set_column_data {
        min-width: 160px;
        border-radius: 7px;
        border: 1px solid #cfdceb;
        background: #fff;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    }

    .upload-preview-table .set_column_data:focus {
        border-color: #5ea2e6;
        box-shadow: 0 0 0 3px rgba(94, 162, 230, 0.18);
    }

    .upload-preview-table .set_column_data.is-selected {
        border-color: #6ec18f;
        box-shadow: 0 0 0 3px rgba(110, 193, 143, 0.15);
        animation: selectPick 0.28s ease;
    }

    @keyframes selectPick {
        0% { transform: scale(1); }
        40% { transform: scale(1.03); }
        100% { transform: scale(1); }
    }

    .mapping-select-cell.is-active {
        background: #eef9f1;
        box-shadow: inset 0 0 0 1px #c8e8d4;
    }

    .column-header-cell {
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    .column-header-cell.is-linked {
        background: #e6f7ed !important;
        color: #1f7a43 !important;
    }

    .upload-mapping-shell.is-compact .upload-preview-table thead tr.active th,
    .upload-mapping-shell.is-compact .upload-preview-table thead tr.mapping-select-row th,
    .upload-mapping-shell.is-compact .upload-preview-table td {
        padding: 4px 6px;
        font-size: 11px;
        line-height: 1.2;
    }

    .upload-mapping-shell.is-compact .upload-preview-table .set_column_data {
        min-width: 120px;
        height: 26px;
        padding: 3px 6px;
        font-size: 11px;
    }

    .upload-mapping-shell.is-compact .upload-preview-table .mapping-select-row th {
        top: 32px;
    }

    @media (max-width: 1024px) {
        .upload-mapping-grid {
            grid-template-columns: 1fr;
        }

        .upload-mapping-header {
            flex-direction: column;
        }

        .upload-mapping-actions {
            justify-content: flex-start;
        }

        .upload-preview-table .mapping-select-row th {
            top: 38px;
        }
    }
</style>

<script>
    (function () {
        var shell = $('.upload-mapping-shell');
        var requiredFields = ['country', 'date_report', 'platform', 'isrc', 'count', 'amount'];
        var requiredFieldsCount = requiredFields.length;
        var animatedCounterValue = 0;

        function getAggregatorId() {
            var idFromShell = parseInt(shell.data('aggregator-id'), 10);
            if (!isNaN(idFromShell) && idFromShell > 0) {
                return idFromShell;
            }

            var fallback = parseInt($('#uploadreport-aggregatorid').val(), 10);
            return isNaN(fallback) ? 0 : fallback;
        }

        function getStorageKey() {
            return 'aggregator_upload_mapping_' + getAggregatorId();
        }

        function saveMappingToStorage() {
            var aggregatorId = getAggregatorId();
            if (!aggregatorId || typeof window.localStorage === 'undefined') {
                return;
            }

            var mapping = {};
            $('.set_column_data').each(function () {
                var value = $(this).val();
                var column = String($(this).data('column_number'));
                if (value) {
                    mapping[column] = value;
                }
            });

            var payload = {
                version: 1,
                aggregator_id: aggregatorId,
                mapping: mapping,
                updated_at: Date.now()
            };

            try {
                window.localStorage.setItem(getStorageKey(), JSON.stringify(payload));
            } catch (e) {
                // ignore storage errors
            }
        }

        function clearMappingStorage() {
            if (typeof window.localStorage === 'undefined') {
                return;
            }

            try {
                window.localStorage.removeItem(getStorageKey());
            } catch (e) {
                // ignore storage errors
            }
        }

        function applyStoredMapping() {
            if (typeof window.localStorage === 'undefined') {
                return false;
            }

            var raw;
            try {
                raw = window.localStorage.getItem(getStorageKey());
            } catch (e) {
                return false;
            }

            if (!raw) {
                return false;
            }

            var parsed;
            try {
                parsed = JSON.parse(raw);
            } catch (e) {
                return false;
            }

            if (!parsed || !parsed.mapping || typeof parsed.mapping !== 'object') {
                return false;
            }

            var assignedFields = {};
            var hasAny = false;

            $('.set_column_data').each(function () {
                var columnKey = String($(this).data('column_number'));
                var value = parsed.mapping[columnKey] || '';

                if (value && requiredFields.indexOf(value) !== -1 && !assignedFields[value]) {
                    $(this).val(value);
                    assignedFields[value] = true;
                    hasAny = true;
                } else {
                    $(this).val('');
                }
            });

            return hasAny;
        }

        function normalizeHeader(value) {
            return String(value || '')
                .toLowerCase()
                .replace(/\s+/g, ' ')
                .trim();
        }

        function headerMatches(field, normalizedHeader) {
            var patterns = {
                isrc: ['isrc', 'код треку', 'track code'],
                country: ['country', 'країна', 'territory', 'region'],
                date_report: ['date', 'month', 'report', 'period', 'місяць', 'дата', 'період'],
                platform: ['platform', 'service', 'store', 'source', 'платформа', 'сервіс'],
                count: ['count', 'qty', 'quantity', 'streams', 'plays', 'views', 'units', 'кількість', 'переглядів', 'прослух'],
                amount: ['amount', 'revenue', 'royalty', 'income', 'sum', 'net', 'сума', 'дохід', 'винагород']
            };

            var list = patterns[field] || [];
            for (var i = 0; i < list.length; i++) {
                if (normalizedHeader.indexOf(list[i]) !== -1) {
                    return true;
                }
            }
            return false;
        }

        function autoMapColumnsByHeaders() {
            var assignedFields = {};
            var selectedByColumn = {};

            $('.column-header-cell').each(function () {
                var columnIndex = parseInt($(this).data('column-index'), 10);
                var header = normalizeHeader($(this).text());

                for (var i = 0; i < requiredFields.length; i++) {
                    var field = requiredFields[i];
                    if (assignedFields[field]) {
                        continue;
                    }
                    if (headerMatches(field, header)) {
                        assignedFields[field] = true;
                        selectedByColumn[columnIndex] = field;
                        break;
                    }
                }
            });

            $('.set_column_data').each(function () {
                var columnIndex = parseInt($(this).data('column_number'), 10);
                var field = selectedByColumn[columnIndex] || '';
                $(this).val(field);
            });

            $('.set_column_data').trigger('change');
        }

        function setAnimatedCounter(target) {
            var start = animatedCounterValue;
            var direction = target >= start ? 1 : -1;
            var frame = 0;
            var totalFrames = 10;

            function tick() {
                frame++;
                var progress = frame / totalFrames;
                var next = Math.round(start + (target - start) * progress);
                $('#selected_field_counter').text(next + ' / ' + requiredFieldsCount);

                if (frame < totalFrames) {
                    requestAnimationFrame(tick);
                } else {
                    animatedCounterValue = target;
                    $('#selected_field_counter').text(target + ' / ' + requiredFieldsCount);
                }
            }

            if (start === target) {
                $('#selected_field_counter').text(target + ' / ' + requiredFieldsCount);
                return;
            }

            $('#selected_field_counter').addClass('counter-animate');
            window.setTimeout(function () {
                $('#selected_field_counter').removeClass('counter-animate');
            }, 380);

            if (direction !== 0) {
                requestAnimationFrame(tick);
            }
        }

        function syncSelectOptions() {
            var used = {};

            $('.set_column_data').each(function () {
                var current = $(this).val();
                if (current) {
                    used[current] = true;
                }
            });

            $('.set_column_data').each(function () {
                var current = $(this).val();

                $(this).find('option').each(function () {
                    var optionValue = $(this).val();
                    if (!optionValue) {
                        return;
                    }

                    var disableOption = !!used[optionValue] && optionValue !== current;
                    $(this).prop('disabled', disableOption);
                });
            });
        }

        function normalizeUniqueFieldAssignments() {
            var used = {};
            var changed = false;

            $('.set_column_data').each(function () {
                var value = $(this).val();
                if (!value) {
                    return;
                }

                if (used[value]) {
                    $(this).val('');
                    changed = true;
                    return;
                }

                used[value] = true;
            });

            return changed;
        }

        function updateColumnHighlights() {
            $('.set_column_data').each(function () {
                var value = $(this).val();
                var columnIndex = $(this).data('column_number');
                var header = $('.column-header-cell[data-column-index="' + columnIndex + '"]');
                var cell = $('.mapping-select-cell[data-column-index="' + columnIndex + '"]');

                $(this).toggleClass('is-selected', !!value);
                header.toggleClass('is-linked', !!value);
                cell.toggleClass('is-active', !!value);
            });
        }

        function updateMappingProgress() {
            normalizeUniqueFieldAssignments();
            syncSelectOptions();

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
                var selected = !!chosen[field];

                item.toggleClass('is-selected', selected);
                item.find('.mapping-required-state').text(selected ? 'Обрано' : 'Не обрано');

                if (selected) {
                    count++;
                }
            });

            var percent = Math.round((count / requiredFieldsCount) * 100);
            var ready = count === requiredFieldsCount;

            $('#mapping_progress_bar').css('width', percent + '%').attr('aria-valuenow', percent);
            $('#mapping_progress_text').text(count + ' / ' + requiredFieldsCount);
            $('#mapping_status')
                .text(ready ? 'Готово до імпорту' : 'Очікує вибору полів')
                .toggleClass('is-ready', ready);

            $('#import').prop('disabled', !ready);
            setAnimatedCounter(count);
            updateColumnHighlights();
            saveMappingToStorage();
        }

        $(document).on('click', '#mapping_reset', function () {
            clearMappingStorage();
            $('.set_column_data').val('');
            $('.set_column_data').trigger('change');
        });

        $(document).on('click', '#mapping_toggle_compact', function () {
            var shell = $('.upload-mapping-shell');
            var isCompact = shell.toggleClass('is-compact').hasClass('is-compact');
            $(this)
                .attr('aria-pressed', isCompact ? 'true' : 'false')
                .text(isCompact ? 'Звичайний режим' : 'Компактний режим');
        });

        $(document).on('change', '.set_column_data', function () {
            normalizeUniqueFieldAssignments();
            syncSelectOptions();
            updateMappingProgress();
        });

        $(function () {
            var restored = applyStoredMapping();
            if (!restored) {
                autoMapColumnsByHeaders();
            }
            syncSelectOptions();
            updateMappingProgress();
        });
    })();
</script>
