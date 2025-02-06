<?php

/* @var $this yii\web\View */
/* @var $all_euro array */
/* @var $all_uah array */
/* @var $costs_euro array */
/* @var $costs_uah array */

?>
<div class="header-page">
    <h3 style="text-align: center">Звіт за 3 кавртал</h3>
    <p></p>
</div>

<div class="body-page">
    <h4 class="text-center">Баланс</h4>
    <table class="table table-striped table-bordered" border="1">
        <tbody>
        <?php if (count($all_euro)) { foreach ($all_euro as $item) { ?>
        <tr><td><?=$item['name']?></td><td><?=round($item['sum'], 2)?></td><td><?=$item['currency_name']?></td></tr>
        <?php }} ?>
        <tr><td></td><td></td><td></td></tr>
        <?php if (count($all_uah)) { foreach ($all_uah as $item) { ?>
            <tr><td><?=$item['name']?></td><td><?=round($item['sum'], 2)?></td><td><?=$item['currency_name']?></td></tr>
        <?php }} ?>
        </tbody>
    </table>
</div>

<?php if ($costs_euro || $costs_uah) { ?>
<div class="body-page">
    <h4 class="text-center">Витрати</h4>
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
        <?php if (count($costs_euro)) { foreach ($costs_euro as $cost) { ?>
            <tr>
                <td><?=$cost['date_item']?></td>
                <td><?=$cost['invoice_type_name']?></td>
                <td><?=$cost['a_name']?></td>
                <td><?=$cost['t_name']?></td>
                <td><?=$cost['description']?></td>
                <td><?=$cost['amount']?></td>
                <td><?=$cost['currency_name']?></td>
            </tr>
        <?php }} ?>
        <?php if (count($costs_uah)) { foreach ($costs_uah as $cost) { ?>
            <tr>
                <td><?=$cost['date_item']?></td>
                <td><?=$cost['invoice_type_name']?></td>
                <td><?=$cost['a_name']?></td>
                <td><?=$cost['t_name']?></td>
                <td><?=$cost['description']?></td>
                <td><?=$cost['amount']?></td>
                <td><?=$cost['currency_name']?></td>
            </tr>
        <?php }} ?>
        </tbody>
    </table>
</div>
<?php } ?>