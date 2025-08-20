<?php

use backend\widgets\DateFormat;
use backend\widgets\Number;

/* @var $this yii\web\View
* @var $model backend\models\InvoiceItems
* @var $tracks array
* @var $feats array
* @var $quarterDate array
*/

$amount = abs($model->invoice->currency_id != 2 ? round($model->amount, 2) * $model->invoice->exchange : round($model->amount, 2));
$pdv = number_format(round($amount * 0.18, 2), 2, '.', '');
$v_zbir = number_format(round($amount * 0.05, 2), 2, '.', '');

$total = number_format($amount - $pdv - $v_zbir,2, '.', '');
$totalAmount = number_format($amount, 2, '.', '');

$name = explode(' ', $model->artist->full_name);

?>

<div class="header-page">
    <div style="text-align: center">
        <img src="/img/blackbeats.png" style="width: 200px;"alt="BLACKBEATS" />
    </div>
    <h3 style="text-align: center;"><b>Акт-Звіт №<?=$model->artist->id . '/' . $model->invoice_id?></b></h3>
    <table width="100%">
        <tr width="100%">
            <td width="50%" style="text-align: left"><b>м.Київ</b></td>
            <td width="50%" style="text-align: right"><b><?=DateFormat::datumUah($model->invoice->date_pay)?></b></td>
        </tr>
    </table>
    <br>
    <p style="text-align: justify">до Договору про передачу виключних авторських і суміжних прав <?=$model->artist->contract?> p.</p>
    <p style="text-align: justify"><b>ТОВАРИСТВО З ОБМЕЖЕНОЮ ВІДПОВІДАЛЬНІСТЮ «БЛЕК БІТС»</b>, в особі директора Комара А.С., який діє на підставі Статуту, іменований надалі - «Ліцензіат», з одного боку, і</p>
    <?php if ($model->artist->artist_type_id == 1) { ?>
        <p style="text-align: justify"><b>Громадянин України <?=$model->artist->full_name?></b>, <?php if(!empty($model->artist->ipn)) { echo 'РНОКПП: ' . $model->artist->ipn . ', ';} ?>надалі - «Ліцензіар» з іншого боку, далі спільно іменовані «Сторони», а кожна окремо – «Сторона»</p>
   <?php } else { ?>
        <p><b>ТОВАРИСТВО З ОБМЕЖЕНОЮ ВІДПОВІДАЛЬНІСТЮ «<?=$model->artist->tov_name?>»</b>, в особі директора <?=$model->artist->full_name?>, яка діє на підставі Статуту, іменоване надалі - «Ліцензіар» з іншого боку, далі спільно іменовані «Сторони», а кожна окремо – «Сторона»</p>
    <?php } ?>

    <p style="text-align: justify">уклали цей Акт-Звіт №<?=$model->artist->id . '/' . $model->invoice_id?> про нарахувння Роялті за період з <?=DateFormat::datumUah($quarterDate['start'])?> по <?=DateFormat::datumUah($quarterDate['end'])?></p>
</div>
<div class="body-page">
    <table class="table table-striped table-bordered" border="1">
        <thead>
        <tr>
            <td>№</td>
            <td>Виконавець</td>
            <td>Назва твору</td>
            <td>Кіл-ть використань</td>
            <td>Частка авторських (суміжних) прав, %</td>
            <td>Загальна сума отриманої винагороди видавцем</td>
            <td>Ставка винагороди правовласника за авторські та суміжні права, %</td>
            <td>Сума Роялті</td>
            <!--<td>Вид прав</td>
            <td>Тип використання</td>
             <td>Тип та/або ресурс використання</td>
             <td>Країна</td>
             <td>Період використання Об'єкта</td>-->
        </tr>
        </thead>
        <tbody>
        <?php
        $i = 1;
        $t = $t2 = 0;
        foreach ($tracks as $item) {
            $t += round($item['amount'], 4);
            $_amount = round($item['amount'] * ($item['percentage_label'] / 100), 4);
            $t2 +=$_amount;

            if ($_amount > 0) {
            ?>
            <tr>
                <td><?=$i?></td>
                <td><?=$item['artist_name']?></td>
                <td><?=rtrim($item['track_name'], '12')?></td>
                <td><?=$item['count']?></td>
                <td><?=$item['percentage']?></td>
                <td><?=round($item['amount'], 4)?></td>
                <td><?=$item['percentage_label']?></td>
                <td><?=$_amount?></td>
                <!--<td><?=$item['prav1']?></td>
                <td><?=$item['prav2']?></td>
                <td><?=$item['platform']?></td>
                <td><?=$item['country']?></td>
                <td><?=DateFormat::datumUah2($item['date_report'])?></td>-->
            </tr>
            <?php
            $i++;
            }
        }

        foreach ($feats as $item) {
            $t += round($item['amount'], 4);
            $_amount = round($item['amount'] * ($item['percentage_label'] / 100), 4);
            $t2 +=$_amount;

            if ($_amount > 0) {
            ?>
            <tr>
                <td><?=$i?></td>
                <td><?=$item['artist_name']?> (<?=$item['feat_name']?>)</td>
                <td><?=rtrim($item['track_name'], '12')?></td>
                <td><?=$item['count']?></td>
                <td><?=$item['percentage']?></td>
                <td><?=round($item['amount'], 4)?></td>
                <td><?=$item['percentage_label']?></td>
                <td><?=$_amount?></td>
                <!--<td><?=$item['prav1']?></td>
                <td><?=$item['prav2']?></td>
                <td><?=$item['platform']?></td>
                <td><?=$item['country']?></td>
                <td><?=DateFormat::datumUah2($item['date_report'])?></td>-->
            </tr>
            <?php
            $i++;
            }
        }
        ?>
        </tbody>
    </table>
</div>
<?php if ($model->artist->artist_type_id == 1) {
    $first_name = $name[0] ?? '';
    $second_name = $name[1] ?? '';
    $last_name = $name[2] ?? '';
    ?>
<div class="footer">
    <p>Всього сума Роялті Правовласника за період з <?= DateFormat::datumUah($quarterDate['start'])?> по <?=DateFormat::datumUah($quarterDate['end'])?> склала <?=$totalAmount?> грн. <span>(<?=Number::num2str($totalAmount)?>)</span>, без ПДВ, згідно ст.196 п.196.1.6. ПКУ, <?php if ($model->invoice->currency_id != 2) { echo 'що є еквівалентом ' . round(abs($model->amount), 2) .' '. $model->invoice->currency->currency_symbol . ' за курсом ' . $model->invoice->exchange . ' на дату підписання Акту про розподіл винагороди.'; } ?></p>
    <p>На день виплати Роялті Правовласнику Видавець зобов'язаний утримати та перерахувати в Державний Бюджет ПДФО у розмірі 18%, який складає <?=$pdv?> <span>(<?=Number::num2str($pdv)?>)</span></p>
    <p>На день виплати Роялті Правовласнику Видавець зобов'язаний утримати та перерахувати в Державний Бюджет Військовий Збір у розмірі 5%, який складає <?=$v_zbir?> <span>(<?=Number::num2str($v_zbir)?>)</span></p>
    <p>Сума, яка підлягає виплаті Правовласнику складає <b><?=$total?> грн. <span>(<?=Number::num2str($total)?>)</span></b>, без ПДВ, згідно ст.196 п.196.1.6. ПКУ.</p>
    <p>Сторони претензій одна до одної не мають.</p>
    <p>Даний Акт-Звіт №<?=$model->artist->id . '/' . $model->invoice_id?> є невід'ємною частиною Договору № <?=$model->artist->contract?> р., має рівнозначну з ним юридичну силу, укладений в двох екземплярах, по одному для кожної із Сторін.</p>
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
<?php } else {

    $s = explode(' ', $model->artist->full_name);
    $s1 = !empty($s[0]) ? $s[0] : '';
    $s2 = !empty($s[1]) ? $s[1] : '';
    $s3 = !empty($s[2]) ? $s[2] : '';
    ?>
    <div class="footer">
        <p>Всього сума Роялті Правовласника за період з <?= DateFormat::datumUah($quarterDate['start'])?> по <?= DateFormat::datumUah($quarterDate['end']) ?> склала <?=$totalAmount?> грн. <span style="">(<?=Number::num2str($totalAmount)?>)</span>, без ПДВ, згідно ст.196 п.196.1.6. ПКУ, <?php if ($model->invoice->currency_id != 2) { echo 'що є еквівалентом ' . abs(round($model->amount, 2)) . ' '. $model->invoice->currency->currency_symbol . ' за курсом ' . $model->invoice->exchange . ' на дату підписання Акту про розподіл винагороди.'; } ?></p>
            <br><p>Сума, яка підлягає виплаті Правовласнику складає <b><?=round($amount, 2)?> грн. <span>(<?=Number::num2str($amount)?>)</span></b>, без ПДВ, згідно ст.196 п.196.1.6. ПКУ.</p>
            <p>Сторони претензій одна до одної не мають.</p>
            <p>Даний Акт-Звіт № <?=$model->artist->id . '/' . $model->invoice_id?> є невід'ємною частиною Договору № <?=$model->artist->contract?> р., має рівнозначну з ним юридичну силу, укладений в двох екземплярах, по одному для кожної із Сторін.</p>
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
                    <td><b>ТОВ «БЛЕК БІТС»</b></td>
                    <td><b>ТОВ «<?=$model->artist->tov_name?>»</b></td>
                </tr>
                <tr>
                    <td>Код ЄДРПОУ 43063818</td>
                    <td>Код ЄДРПОУ <?=$model->artist->edrpou?></td>
                </tr>
                <tr>
                    <td>Україна, 01033, м. Київ, вул. Василя Яна, 3/5, оф. 409</td>
                    <td><?=$model->artist->address?></td>
                </tr>
                <tr>
                    <td>Банківські реквізити:</td>
                    <td>Банківські реквізити:</td>
                </tr>
                <tr>
                    <td>IBAN UA533003350000000026003647446</td>
                    <td>IBAN <?=$model->artist->iban?></td>
                </tr>
                <tr>
                    <td>АТ "Райффайзен Банк АВАЛЬ"</td>
                    <td><?=$model->artist->bank?></td>
                </tr>
                <tr>
                    <td>МФО:300335</td>
                    <td>МФО:<?=$model->artist->mfo?></td>
                </tr>
                <tr>
                    <td>Тел.:+380633143435</td>
                    <td><?=!empty($model->artist->phone) ? 'Тел.:+'.$model->artist->phone : ''?></td>
                </tr>
                <tr>
                    <td>Платник податку на прибуток на загальних підставах</td>
                    <td><?=!empty($model->artist->description) ? $model->artist->description : 'Платник податку на прибуток на загальних підставах'?></td>
                </tr>
                <tr>
                    <td><b>Директор</b></td>
                    <td><b>Директор</b></td>
                </tr>
                <tr>
                    <td><b>_______________/Комар А.С./</b></td>
                    <td><b>_______________/<?=$s1?> <?=$s2?>/</b></td>
                </tr>
                <tr>
                    <td><b>М.П.</b></td>
                    <td><b>М.П.</b></td>
                </tr>
                </tbody>
            </table>
    </div>
<?php }