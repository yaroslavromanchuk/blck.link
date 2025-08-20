<?php

/* @var $this yii\web\View */
/* @var $model backend\models\InvoiceItems */
/* @var $all array */
/* @var $costs array */

?>
<div class="header-page">
    <div style="text-align: center">
        <img src="/img/blackbeats.png" style="width: 200px;"alt="BLACKBEATS" />
    </div>
    <h3 style="text-align: center"><b>Звіт №<?=$model->id?></b></h3>
    <p></p>
</div>

<div class="body-page">
    <h4 class="text-center"><b>Баланс</b></h4>
    <table class="table table-striped table-bordered" border="1">
        <tbody>
        <?php foreach ($all as $item) { ?>
        <tr>
            <td><?=$item['name']?></td>
            <td><?=number_format(round($item['value'], 2), 2, '.', '')?></td>
            <td><?=$item['currency_name']?></td>
        </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<?php if ($costs) { ?>
<div class="body-page">
    <h4 class="text-center"><b>Витрати</b></h4>
    <table class="table table-striped table-bordered" border="1">
        <thead>
        <tr>
            <td>Дата</td>
            <td>Тип</td>
            <td>Виконавець</td>
            <td>Трек</td>
            <td>Стаття витрат</td>
            <td>Сума</td>
            <td>Валюта</td>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($costs as $cost) { ?>
            <tr>
                <td><?=$cost['date_item']?></td>
                <td><?=$cost['invoice_type_name']?></td>
                <td><?=$cost['a_name']?></td>
                <td><?=$cost['t_name']?></td>
                <td><?=$cost['description']?></td>
                <td><?=number_format(round($cost['amount'],2), 2, '.', '')?></td>
                <td><?=$cost['currency_name']?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
<?php } ?>