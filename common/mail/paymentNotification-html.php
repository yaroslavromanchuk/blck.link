<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $InvoiceItems \backend\models\InvoiceItems */

$t = static fn(string $source): string => $InvoiceItems->artist->translateDocument($source);

$logoUrl = Yii::getAlias('@site') . '/images/email_label.jpeg';
$registerUrl = 'https://www.youtube.com/watch?v=4qTOAmGPtDg';
$signUrl = 'https://www.youtube.com/watch?v=7WdRAjprR6Y';
$helpUrl = 'https://help.vchasno.com.ua/poperednij-pereglyad-dokumentiv-pidpysujte-bez-reyestracziyi/';
?>
<!doctype html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Html::encode($t('Royalty Report')) ?></title>
</head>
<body style="margin:0; padding:0; background:#f3f5f7; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <div style="display:none; max-height:0; overflow:hidden; opacity:0;">
        <?= Html::encode($t('Готовий Ваш звіт за підсумками розподілу винагороди за') . ' ' . $InvoiceItems->invoice->quarter . ' ' . $t('кв.') . ' ' . $InvoiceItems->invoice->year . ' ' . $t('р.')) ?>
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f3f5f7;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="680" cellspacing="0" cellpadding="0" border="0" style="width:100%; max-width:680px; background:#ffffff; border-radius:10px; overflow:hidden; border:1px solid #e5e7eb;">
                    <tr>
                        <td align="center" style="padding:0; background:#171a2b; line-height:0; font-size:0;">
                            <?= Html::img($logoUrl, ['alt' => 'Black Beats', 'width' => '680', 'style' => 'display:block; width:100%; max-width:680px; height:auto; border:0;']) ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:34px 38px 12px;">
                            <h1 style="margin:0 0 16px; font-size:26px; line-height:34px; color:#171a2b;">
                                <?= Html::encode($t('Вітаємо') . ' ' . $InvoiceItems->artist->name . '!') ?>
                            </h1>
                            <p style="margin:0; font-size:16px; line-height:26px; color:#4b5563;">
                                <?= Html::encode($t('Готовий Ваш звіт за підсумками розподілу винагороди за')) ?>
                                <strong><?= (int)$InvoiceItems->invoice->quarter ?> <?= Html::encode($t('кв.')) ?> <?= (int)$InvoiceItems->invoice->year ?> <?= Html::encode($t('р.')) ?></strong>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 38px 28px;">
                            <h2 style="margin:0 0 18px; font-size:20px; line-height:28px; color:#171a2b;">
                                <?= Html::encode($t('Що потрібно зробити для отримання роялті')) ?>:
                            </h2>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:16px; border:1px solid #e5e7eb; border-radius:8px;">
                                <tr>
                                    <td width="44" valign="top" align="center" style="padding:18px 8px 18px 14px;">
                                        <div style="width:32px; height:32px; line-height:32px; border-radius:50%; background:#5b67f1; color:#ffffff; font-weight:bold;">1</div>
                                    </td>
                                    <td style="padding:16px 18px 18px 8px;">
                                        <h3 style="margin:0 0 8px; font-size:16px; color:#171a2b;"><?= Html::encode($t('Відповісти на цей лист')) ?></h3>
                                        <p style="margin:0 0 10px; font-size:14px; line-height:22px; color:#4b5563;">
                                            <?= Html::encode($t('Якщо банківські реквізити не змінилися, просто напишіть нам у відповідь:')) ?>
                                        </p>
                                        <div style="padding:10px 12px; background:#f6f7fb; border-left:3px solid #5b67f1; font-size:14px; color:#111827;">
                                            <?= Html::encode($t('Реквізити без змін')) ?>
                                        </div>
                                        <p style="margin:12px 0 6px; font-size:14px; line-height:22px; color:#4b5563;">
                                            <?= Html::encode($t('Якщо реквізити змінилися, надішліть нам у відповіді:')) ?>
                                        </p>
                                        <ul style="margin:0; padding:0 0 0 18px; font-size:14px; line-height:24px; color:#111827;">
                                            <li><?= Html::encode($t('IBAN рахунку')) ?></li>
                                            <li><?= Html::encode($t('Назва банку')) ?></li>
                                        </ul>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:16px; border:1px solid #e5e7eb; border-radius:8px;">
                                <tr>
                                    <td width="44" valign="top" align="center" style="padding:18px 8px 18px 14px;">
                                        <div style="width:32px; height:32px; line-height:32px; border-radius:50%; background:#5b67f1; color:#ffffff; font-weight:bold;">2</div>
                                    </td>
                                    <td style="padding:16px 18px 18px 8px;">
                                        <h3 style="margin:0 0 8px; font-size:16px; color:#171a2b;"><?= Html::encode($t('Підписати акт-звіт у сервісі «Вчасно»')) ?></h3>
                                        <p style="margin:0 0 12px; font-size:14px; line-height:22px; color:#4b5563;">
                                            <?= Html::encode($t('Посилання на документ для підписання надійде Вам окремим листом на електронну пошту.')) ?>
                                            <?= Html::encode($t('Для здійснення виплати необхідно підписати акт-звіт до 10 числа наступного місяця.')) ?>
                                        </p>
                                        <div style="padding:10px 12px; background:#fff7ed; border-left:3px solid #f59e0b; font-size:13px; line-height:21px; color:#7c2d12;">
                                            <?= Html::encode($t('Якщо акт-звіт не буде підписаний до 10 числа наступного місяця, виплата автоматично переноситься на наступний квартал.')) ?>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:10px; border:1px solid #e5e7eb; border-radius:8px;">
                                <tr>
                                    <td width="44" valign="top" align="center" style="padding:18px 8px 18px 14px;">
                                        <div style="width:32px; height:32px; line-height:32px; border-radius:50%; background:#5b67f1; color:#ffffff; font-weight:bold;">3</div>
                                    </td>
                                    <td style="padding:16px 18px 18px 8px;">
                                        <h3 style="margin:0 0 8px; font-size:16px; color:#171a2b;"><?= Html::encode($t('Отримати виплату')) ?></h3>
                                        <p style="margin:0; font-size:14px; line-height:22px; color:#4b5563;">
                                            <?= Html::encode($t('Після підписання документа у сервісі «Вчасно» роялті будуть виплачені протягом 5 робочих днів.')) ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:8px 38px 28px;">
                            <h2 style="margin:0 0 14px; font-size:18px; color:#171a2b;">
                                <?= Html::encode($t('Інструкції для сервісу «Вчасно»')) ?>
                            </h2>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:12px;">
                                <tr>
                                    <td width="50%" valign="top" style="padding-right:6px;">
                                        <a href="<?= Html::encode($registerUrl) ?>" style="text-decoration:none; color:#171a2b;">
                                            <img src="https://img.youtube.com/vi/4qTOAmGPtDg/hqdefault.jpg" alt="" width="300" style="display:block; width:100%; height:auto; border-radius:8px;">
                                            <div style="padding:10px 4px 4px; font-size:14px; line-height:20px; font-weight:bold;">
                                                <?= Html::encode($t('Як зареєструватися у сервісі «Вчасно»')) ?>
                                            </div>
                                        </a>
                                    </td>
                                    <td width="50%" valign="top" style="padding-left:6px;">
                                        <a href="<?= Html::encode($signUrl) ?>" style="text-decoration:none; color:#171a2b;">
                                            <img src="https://img.youtube.com/vi/7WdRAjprR6Y/hqdefault.jpg" alt="" width="300" style="display:block; width:100%; height:auto; border-radius:8px;">
                                            <div style="padding:10px 4px 4px; font-size:14px; line-height:20px; font-weight:bold;">
                                                <?= Html::encode($t('Як підписати вхідний документ у «Вчасно»')) ?>
                                            </div>
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:10px 0 0; font-size:14px; line-height:22px; text-align:center;">
                                <a href="<?= Html::encode($helpUrl) ?>" style="color:#5b67f1; text-decoration:underline;">
                                    <?= Html::encode($t('Відкрити довідковий центр «Вчасно»')) ?>
                                </a>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:24px 38px 34px; background:#171a2b;">
                            <p style="margin:0 0 6px; font-size:16px; line-height:24px; color:#ffffff;"><?= Html::encode($t('Дякуємо!')) ?></p>
                            <p style="margin:0; font-size:14px; line-height:22px; color:#cbd5e1;"><?= Html::encode($t('Команда Black Beats')) ?></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

