<?php

use backend\widgets\DateFormat;
use backend\widgets\Number;

/* @var $this yii\web\View
 * @var $artist backend\models\Artist
 * @var $invoice_id int
 * @var $date_pay string
 * @var $quarterDate array
 * @var $amount float
 * @var $pdv float
 * @var $v_zbir float
 * @var $total float
*/

$pdv = number_format($pdv, 2, '.', '');
$v_zbir = number_format($v_zbir, 2, '.', '');
$total = number_format($total,2, '.', '');
$totalAmount = number_format($amount, 2, '.', '');
?>

<div class="header-page" style="padding-top: 15px">
    <div style="text-align: center;">
        <img src="/img/blackbeats.png" style="width: 200px;"alt="BLACKBEATS" />
    </div>
    <h3 style="text-align: center;"><b>Акт-Звіт № <?=$artist->id . '/' . $invoice_id?></b></h3>
    <table width="100%">
        <tr width="100%">
            <td width="50%" style="text-align: left"><b>м. Київ</b></td>
            <td width="50%" style="text-align: right"><b><?=DateFormat::datumUah($date_pay)?></b></td>
        </tr>
    </table>
    <br>
    <p style="text-align: center">до Ліцензійного Договору № <?=$artist->contract?> p.</p>
    <p style="text-align: justify"><b>ТОВАРИСТВО З ОБМЕЖЕНОЮ ВІДПОВІДАЛЬНІСТЮ «БЛЕК БІТС»</b>, в особі директора Комара А.С., який діє на підставі Статуту, іменований надалі - «Ліцензіат», з одного боку, і</p>
    <?php if ($artist->artist_type_id == 1) { ?>
        <p style="text-align: justify"><b>Громадянин України <?=$artist->full_name?></b>, <?php
            if ($artist->id == 387) {
                echo 'паспорт серії СТ No 227500, виданий Васильківським МВ ГО ГУ МВС України в Київській обл. від 10.11.2010 року, ';
            } else if(!empty($artist->ipn)) {
                echo 'РНОКПП: ' . $artist->ipn . ', ';
            }
            ?>надалі - «Ліцензіар» з іншого боку, далі спільно іменовані «Сторони», а кожна окремо – «Сторона»</p>
   <?php } else { ?>
        <p><b>ТОВАРИСТВО З ОБМЕЖЕНОЮ ВІДПОВІДАЛЬНІСТЮ «<?=$artist->tov_name?>»</b>, в особі директора <?=$artist->full_name?>, яка діє на підставі Статуту, іменоване надалі - «Ліцензіар» з іншого боку, далі спільно іменовані «Сторони», а кожна окремо – «Сторона»</p>
    <?php } ?>

    <p style="text-align: justify">уклали цей Акт-Звіт № <?=$artist->id . '/' . $invoice_id?> про нарахування Роялті за період з <?=DateFormat::datumUah($quarterDate['start'])?> по <?=DateFormat::datumUah($quarterDate['end'])?></p>
</div>

<?php
// Розбити на слова
$words = preg_split('/\s+/', trim($artist->full_name));

// Прізвище
$lastname = $words[0] ?? '';

// Ініціали (перші літери імені та по батькові)
$initials = '';
if (isset($words[1])) {
    $initials .= mb_substr($words[1], 0, 1) . '. ';
}
if (isset($words[2])) {
    $initials .= mb_substr($words[2], 0, 1) . '.';
}
?>
<div class="footer">
    <p>Всього сума Роялті Ліцензіара за період з <?=DateFormat::datumUah($quarterDate['start'])?> по <?=DateFormat::datumUah($quarterDate['end'])?> склала <?=$totalAmount?> грн. <span>(<?=Number::num2str($totalAmount)?>)</span>, без ПДВ, згідно ст.196 п.196.1.6. ПКУ.</p>
    <?php if ($artist->artist_type_id == 1) { ?>
    <p>На день виплати Роялті Ліцензіару Ліцензіат зобов'язаний утримати та перерахувати в Державний Бюджет ПДФО у розмірі 18%, який складає <?=$pdv?> <span>(<?=Number::num2str($pdv)?>)</span></p>
    <p>На день виплати Роялті Ліцензіару Ліцензіат зобов'язаний утримати та перерахувати в Державний Бюджет Військовий Збір у розмірі 5%, який складає <?=$v_zbir?> <span>(<?=Number::num2str($v_zbir)?>)</span></p>
    <?php } ?>
    <p>Сума, яка підлягає виплаті Ліцензіару складає <b><?=$artist->artist_type_id == 1 ? $total : $totalAmount?> грн. <span>(<?=Number::num2str($artist->artist_type_id == 1 ? $total : $totalAmount)?>)</span></b>, без ПДВ, згідно ст.196 п.196.1.6. ПКУ.</p>
    <p>Сторони претензій одна до одної не мають.</p>
    <p>Даний Акт-Звіт № <?=$artist->id . '/' . $invoice_id?> є невід'ємною частиною Ліцензійного Договору № <?=$artist->contract?> р., має рівнозначну з ним юридичну силу, укладений в двох екземплярах, по одному для кожної із Сторін.</p>
    <br>
    <?php if ($artist->artist_type_id == 1) { ?>
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
                <td><b>Громадянин України <br><?=$artist->full_name?></b></td>
            </tr>
            <tr>
                <td><b>_______________/Комар А.С./</b></td>
                <td><b>_______________/<?=$lastname . ' ' . $initials?>/</b></td>
            </tr>
            </tbody>
        </table>
    <?php } else { ?>
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
                <td><b>ТОВ «<?=$artist->tov_name?>»</b></td>
            </tr>
            <tr>
                <td>Код ЄДРПОУ 43063818</td>
                <td>Код ЄДРПОУ <?=$artist->edrpou?></td>
            </tr>
            <tr>
                <td>Україна, 01033, м. Київ, вул. Василя Яна, 3/5, оф. 409</td>
                <td><?=$artist->address?></td>
            </tr>
            <tr>
                <td>Банківські реквізити:</td>
                <td>Банківські реквізити:</td>
            </tr>
            <tr>
                <td>IBAN UA533003350000000026003647446</td>
                <td>IBAN <?=$artist->iban?></td>
            </tr>
            <tr>
                <td>АТ "Райффайзен Банк АВАЛЬ"</td>
                <td><?=$artist->bank?></td>
            </tr>
            <tr>
                <td>МФО:300335</td>
                <td>МФО:<?=$artist->mfo?></td>
            </tr>
            <tr>
                <td>Тел.:+380633143435</td>
                <td><?=!empty($artist->phone) ? 'Тел.:+'.$artist->phone : ''?></td>
            </tr>
            <tr>
                <td>Платник податку на прибуток на загальних підставах</td>
                <td><?=!empty($artist->description) ? $artist->description : 'Платник податку на прибуток на загальних підставах'?></td>
            </tr>
            <tr>
                <td><b>Директор</b></td>
                <td><b>Директор</b></td>
            </tr>
            <tr>
                <td><b>_______________/Комар А.С./</b></td>
                <td><b>_______________/<?=$lastname . ' ' . $initials?>/</b></td>
            </tr>
            <tr><td></td></tr>
            <tr><td></td></tr>
            <tr>
                <td><b>М.П.</b></td>
                <td><b>М.П.</b></td>
            </tr>
            </tbody>
        </table>
 
<?php } ?>
</div>