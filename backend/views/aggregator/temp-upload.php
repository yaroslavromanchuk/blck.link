<?php

/** @var $count_header int */
/** @var $file_header array */
/** @var $file_data array */

?>

<div class="alert alert-info" style="margin-bottom: 10px;">
    Для імпорту обов'язково визначте всі поля: Країна, ISRC, Місяць звіту, Платформа, Кількість переглядів, Сума.
</div>

<div class="text-right" style="margin-bottom: 10px;">
    <button type="button" name="import" id="import" class="btn btn-success" disabled>Import</button>
</div>

<table class="table table-bordered table-hover table-condensed upload-preview-table">
    <thead>
    <tr class="active">
        <?php for ($count = 0; $count < $count_header; $count++) { ?>
            <th><?= htmlspecialchars((string)($file_header[$count] ?? ('Колонка ' . ($count + 1))), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></th>
        <?php } ?>
    </tr>
    <tr>
        <?php for ($count = 0; $count < $count_header; $count++) { ?>
            <th>
                <label class="sr-only" for="set_column_<?= $count ?>">Вкажіть назву колонки <?= $count + 1 ?></label>
                <select id="set_column_<?= $count ?>" name="set_column_data" class="form-control set_column_data" data-column_number="<?= $count ?>">
                    <option value="">Вкажіть назву колонки</option>
                    <option value="country">Країна</option>
                    <option value="date_report">Місяць звіту</option>
                    <option value="platform">Платформа</option>
                    <option value="isrc">ISRC</option>
                    <option value="count">Кількість переглядів</option>
                    <option value="amount">Сума</option>
                </select>
            </th>
        <?php } ?>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach ($file_data as $row) {
        echo '<tr>';
        foreach ($row as $value) {
            echo '<td>' . strip_tags((string)$value) . '</td>';
        }
        echo '</tr>';
    }
    ?>
    </tbody>
</table>
