<?php

namespace backend\widgets;

class Number
{
    /**
     * Возвращает сумму прописью
     */
    public static function num2str(float $num): string
    {
        $nul = 'нуль';
        $ten = [
            [
                '', 'одна', 'дві', 'три', 'чотири', 'п\'ять', 'шість', 'сім',
                'вісім', 'дев\'ять',
            ],
            [
                '', 'одна', 'дві', 'три', 'чотири', 'п\'ять', 'шість', 'сім',
                'вісім', 'дев\'ять',
            ],
        ];
        $a20 = [
            'десять', 'одиннадцять', 'дванадцять', 'тринадцять', 'чотирнадцять',
            'п\'ятнадцять', 'шістнадцять', 'сімнадцять', 'вісімнадцять',
            'дев\'ятнадцять',
        ];
        $tens = [
            2 => 'двадцять', 'тридцять', 'сорок', 'п\'ятдесят', 'шістдесят',
            'сімдесят', 'вісімдесят', 'дев\'яносто',
        ];
        $hundred = [
            '', 'сто', 'двісті', 'триста', 'чотириста', 'п\'ятсот', 'шістсот',
            'сімсот', 'вісімсот', 'дев\'ятьсот',
        ];
        $unit = [
            ['копійка', 'копійки', 'копійок', 1],
            ['гривня', 'гривні', 'гривень', 0],
            ['тисяча', 'тисячі', 'тисяч', 1],
            ['милліон', 'милліона', 'милліонів', 0],
            ['милліард', 'миліарда', 'милліардів', 0],
        ];
        [$rub, $kop] = explode('.', sprintf('%015.2f', (float) $num));
        $out = [];
        if ((int) $rub > 0) {
            foreach (str_split($rub, 3) as $uk => $v) {
                if (!(int) $v) {
                    continue;
                }
                $uk = sizeof($unit) - $uk - 1;
                $gender = $unit[$uk][3];
                $array_map = [];

                foreach (str_split($v, 1) as $key => $var) {
                    $array_map[$key] = (int) str_split($v, 1)[$key];
                }
                [$i1, $i2, $i3] = $array_map;

                $out[] = $hundred[$i1];
                if ($i2 > 1) {
                    $out[] = $tens[$i2].' '.$ten[$gender][$i3];
                } else {
                    $out[] = $i2 > 0 ? $a20[$i3] : $ten[$gender][$i3];
                } # 10-19 | 1-9
                // units without rub & kop
                if ($uk > 1) {
                    $out[] = self::morph(
                        $v,
                        $unit[$uk][0],
                        $unit[$uk][1],
                        $unit[$uk][2]
                    );
                }
            }
        } else {
            $out[] = $nul;
        }
        $out[] = self::morph(
            (int) $rub,
            $unit[1][0],
            $unit[1][1],
            $unit[1][2]
        );
        $out[] = $kop.' '. self::morph(
                $kop,
                $unit[0][0],
                $unit[0][1],
                $unit[0][2]
            );

        return trim(preg_replace('/ {2,}/', ' ', implode(' ', $out)));
    }

    /**
     * Склоняем словоформу
     */
    private static function morph($n, $f1, $f2, $f5)
    {
        $n = abs($n) % 100;
        if ($n > 10 && $n < 20) {
            return $f5;
        }
        $n %= 10;
        if ($n > 1 && $n < 5) {
            return $f2;
        }
        if ($n === 1) {
            return $f1;
        }

        return $f5;
    }
}