<?php

/** @var $count_header int */
/** @var $file_data array */

?>

<div align="right">
    <button type="button" name="import" id="import" class="btn btn-success" disabled>Import</button>
</div>

<table class="table table-bordered">
    <?php for($count = 0; $count < $count_header; $count++) { ?>
        <th>
            <select name="set_column_data" class="form-control set_column_data" data-column_number="<?=$count?>">
                <option value="">Вкажіть назву колонки</option>
                <option value="country">Країна</option>
                <option value="date_report">Місяць звіту</option>
                <option value="platform">Платформа</option>
                <option value="isrc">ISRC</option>
                <option value="count">Кількість переглядів</option>
                <option value="amount">Сума</option>
            </select>
        </th>
   <?php }
    //echo '<pre>';
    //print_r($file_data);
   // echo '</pre>';
   // exit;

    foreach ($file_data as $row) {
        echo '<tr>';
        foreach ($row as $key => $value) {
            echo '<td>'.strip_tags($value).'</td>';
        }
        echo '</tr>';
    }
    ?>
</table>
