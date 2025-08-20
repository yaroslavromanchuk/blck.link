<?php

use backend\widgets\DateFormat;
use backend\widgets\Number;

/* @var $this yii\web\View */
/* @var $model backend\models\InvoiceItems */
/* @var $items array */
/* @var $feats array */

$amount = abs(round($model->invoice->currency_id == 1 ? $model->amount * $model->invoice->exchange : $model->amount, 2));
$pdv = round($amount * 0.18, 2);
$v_zbir = round($amount * 0.015, 2);

$total = $amount - $pdv - $v_zbir;

$name = explode(' ', $model->artist->full_name);

$first_name = $name[0] ?? '';
$second_name = $name[1] ?? '';
$last_name = $name[2] ?? '';
?>

<div class="header-page">
    <div style="text-align: center">
        <img src="/img/blackbeats.png" style="width: 200px;"alt="BLACKBEATS" />
    </div>
    <h3 style="text-align: center">Акт-Звіт №<?=$model->id?></h3>
    <p style="text-align: center">до Договору про передачу виключних авторських і суміжних прав <?=$model->artist->contract?> p.</p>
    <p>Товариство з обмеженою відповідальністю "БЛЕК БІТС", в особі директора Комара А.С., який діє на підставі Статуту, іменований надалі - «Ліцензіат», з одного боку, і</p>
    <p>Громадянин України <?=$model->artist->full_name?>, надалі - «Ліцензіар» з іншого боку, далі спільно іменовані «Сторони», а кожна окремо – «Сторона»</p>
    <p>уклали цей Акт-Звіт №<?=$model->id?> про нарахувння Роялті за період з <?= DateFormat::datumUah($model->artist->date_last_payment)?> по <?= DateFormat::datumUah() ?></p>
    <p style="text-align: right">
        <span>м. Київ</span>
        <span><?= DateFormat::datumUah() ?></span>
    </p>
</div>
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
            <td>Вид прав</td>
            <td>Вид прав 2</td>
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
                <td><?=$item['track_name']?></td>
                <td><?=$item['count']?></td>
                <td><?=$item['percentage']?></td>
                <td><?=$item['amount']?></td>
                <td><?=$item['pr2']?></td>
                <td><?=round($item['am_2'], 4)?></td>
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
                <td><?=$item['artist_name']?></td>
                <td><?=$item['track_name']?></td>
                <td><?=$item['count']?></td>
                <td><?=$item['percentage']?></td>
                <td><?=$item['amount']?></td>
                <td><?=$item['pr2']?></td>
                <td><?=round($item['am_2'], 4)?></td>
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
       <!-- <tfoot>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td><?php //echo round($t, 2) ?></td>
            <td></td>
            <td><b><?php // echo abs(round($model->amount, 2))?> <?=$model->invoice->currency->currency_name?></b> <?php //$t2 ?></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        </tfoot>-->
    </table>
</div>
<div class="footer">
    <p>Всього сума Роялті Правовласника за період з <?= DateFormat::datumUah($model->artist->date_last_payment)?> по <?= DateFormat::datumUah() ?> склала <?=$amount?> <span style="">(<?=Number::num2str($amount)?>)</span>, без ПДВ, згідно ст.196 п.196.1.6. ПКУ, <?php if ($model->invoice->currency_id == 1) { echo 'що є еквівалентом ' . abs(round($model->amount, 2)) . ' євро за курсом ' . $model->invoice->exchange . ' на дату підписання Акту про розподіл винагороди.'; } ?></p>
    <p>На день виплати Роялті Правовласнику Видавець зобов'язаний утримати та перерахувати в Державний Бюджет ПДФО у розмірі 18%, який складає <?=$pdv?> <span>(<?=Number::num2str($pdv)?>)</span></p>
    <p>На день виплати Роялті Правовласнику Видавець зобов'язаний утримати та перерахувати в Державний Бюджет Військовий Збір у розмірі 1,5%, який складає <?=$v_zbir?> <span>(<?=Number::num2str($v_zbir)?>)</span></p>
    <p><b>Сума, яка підлягає виплаті Правовласнику складає <?=$total?> <span>(<?=Number::num2str($total)?>)</span>, без ПДВ, згідно ст.196 п.196.1.6. ПКУ.</b></p>
    <p><b>Сторони претензій одна до одної не мають.</b></p>
    <p>Даний Акт-Звіт № <?=$model->id?> є невід'ємною частиною Договору № <?=$model->artist->contract?> р., має рівнозначну з ним юридичну силу, укладений в двох екземплярах, по одному для кожної із Сторін.</p>
    <br>
        <table style="width: 100%">
        <thead>
        <tr>
            <td><b>Ліцензіат</b></td>
            <td><b>Ліцензіар</b></td>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><b>Директор ТОВ «БЛЕК БІТС»</b></td>
            <td><b>Громадянин України <br><?=$model->artist->full_name?></b></td>
        </tr>
        <tr>
            <td><b>_______________/Комар А.С./</b></td>
            <td><b>_______________/<?=$first_name?> <?=$second_name?> <?=$last_name?>/</b></td>
        </tr>
        </tbody>
        </table>
</div>
