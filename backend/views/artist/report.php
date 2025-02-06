<?php

use backend\widgets\DateFormat;
use backend\widgets\Number;

/* @var $this yii\web\View */

/* @var $all_euro array */
/* @var $all_uah array */
/* @var $costs array */
/* @var $income array */

/* @var $model backend\models\Artist */
/* @var $items array */
/* @var $feats array */

?>

<div class="header-page">
    <h3 style="text-align: center">Звіт за 3 квартал, <?=$model->name?></h3>
</div>

<div class="body-page">
    <h4 class="text-center">Баланс</h4>
    <table class="table table-striped table-bordered" border="1">
        <tbody>
        <?php if (count($all_euro)) { foreach ($all_euro as $item) { ?>
            <tr><td><?=$item['name']?></td><td><?=number_format(round($item['sum'], 2), 2, '.', '')?></td><td><?=$item['currency_name']?></td></tr>
        <?php }} ?>
        <tr><td></td><td></td><td></td></tr>
        <?php if (count($all_uah)) { foreach ($all_uah as $item) { ?>
            <tr><td><?=$item['name']?></td><td><?=number_format(round($item['sum'], 2), 2, '.', '')?></td><td><?=$item['currency_name']?></td></tr>
        <?php }} ?>
        </tbody>
    </table>
</div>

<?php if ($income) { ?>
    <br><br>
    <div class="body-page">
        <h4 class="text-center">Додавткові надходження</h4>
        <table class="table table-striped table-bordered" border="1">
            <thead>
            <tr>
                <td>Дата</td>
                <td>Виконавець</td>
                <td>Стаття витрат</td>
                <td>Сума</td>
                <td>Валюта</td>
            </tr>
            </thead>
            <tbody>
            <?php if (count($income)) { foreach ($income as $cost) { ?>
                <tr>
                    <td><?=$cost['date_item']?></td>
                    <td><?=$cost['a_name']?></td>
                    <td><?=$cost['description']?></td>
                    <td><?=number_format($cost['amount'],2, '.', '')?></td>
                    <td><?=$cost['currency_name']?></td>
                </tr>
            <?php }} ?>
            </tbody>
        </table>
    </div>
<?php } ?>

<?php if ($costs) { ?>
    <br><br>
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
            <?php if (count($costs)) { foreach ($costs as $cost) { ?>
                <tr>
                    <td><?=$cost['date_item']?></td>
                    <td><?=$cost['invoice_type_name']?></td>
                    <td><?=$cost['a_name']?></td>
                    <td><?=$cost['t_name']?></td>
                    <td><?=$cost['description']?></td>
                    <td><?=number_format($cost['amount'],2, '.', '')?></td>
                    <td><?=$cost['currency_name']?></td>
                </tr>
            <?php }} ?>
            </tbody>
        </table>
    </div>
<?php } ?>

<br>
<div class="body-page">
    <table class="table table-striped table-bordered" border="1">
        <thead>
        <tr>
            <td>№</td>
            <td>Назва Твору</td>
            <td>Виконавець</td>
            <td>Кіл-ть Використань</td>
            <td>Частка авторських (суміжних) прав, %</td>
            <td>Загальна сума отриманої Винагороди Видавцем</td>
            <td>Ставка Винагороди Правовласника за авторські та суміжні права, %</td>
            <td>Сума Роялті правовласника</td>
            <td>Валюта</td>
            <td>Вид прав</td>
            <td>Тип використання</td>
            <td>Тип та/або ресурс використання</td>
            <td>Період використання Об'єкта</td>
        </tr>
        </thead>
        <tbody>
        <?php
        $i = 1;
        $t = $t2 = 0;
        foreach ($items as $item) {
            $t +=$item['amount'];
            $t2 +=$item['am_2'];
            ?>
            <tr>
                <td><?=$i?></td>
                <td><?=$item['artist_name']?></td>
                <td><?=str_replace("1", "", $item['track_name'])?></td>
                <td><?=$item['count']?></td>
                <td><?=$item['percentage']?></td>
                <td><?=$item['amount']?></td>
                <td><?=$item['pr2']?></td>
                <td><?=round($item['am_2'], 4)?></td>
                <td><?=$item['currency_name']?></td>
                <td><?=$item['prav1']?></td>
                <td><?=$item['prav2']?></td>
                <td><?=$item['platform']?></td>
                <td><?=date('F Y', strtotime($item['date_report']))?></td>
            </tr>
            <?php
            $i++;
        }

        foreach ($feats as $item) {
            $t +=$item['amount'];
            $t2 +=$item['am_2'];
            ?>
            <tr>
                <td><?=$i?></td>
                <td><?=$item['artist_name']?> (<?=$item['feat_name']?>)</td>
                <td><?=str_replace("1", "", $item['track_name'])?></td>
                <td><?=$item['count']?></td>
                <td><?=$item['percentage']?></td>
                <td><?=$item['amount']?></td>
                <td><?=$item['pr2']?></td>
                <td><?=round($item['am_2'], 4)?></td>
                <td><?=$item['currency_name']?></td>
                <td><?=$item['prav1']?></td>
                <td><?=$item['prav2']?></td>
                <td><?=$item['platform']?></td>
                <td><?=date('F Y', strtotime($item['date_report']))?></td>
            </tr>
            <?php
            $i++;
        }
        ?>
        </tbody>
    </table>
</div>