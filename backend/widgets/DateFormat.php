<?php

namespace backend\widgets;

/**
 * Get date format
 */
class DateFormat
{
    /**
     * @param string $date
     * @return string
     */
    public static function getQuarterText(string $date = 'now'): string
    {
        $m = (int) date('m', strtotime($date));
        $y = (int) date('Y', strtotime($date));

        $mapArray = [
            1 => '1 кв. ' . $y,
            2 => '1 кв. ' . $y,
            3 => '1 кв. ' . $y,
            4 => '2 кв. ' . $y,
            5 => '2 кв. ' . $y,
            6 => '2 кв. ' . $y,
            7 => '3 кв. ' . $y,
            8 => '3 кв. ' . $y,
            9 => '3 кв. ' . $y,
            10 => '4 кв. ' . $y,
            11 => '4 кв. ' . $y,
            12 => '4 кв. ' . $y,
        ];
        return $mapArray[$m];
    }

    /**
     * @param string $date
     * @return int
     */
    public static function getQuarterNumber(string $date = 'now'): int
    {
        $m = (int) date('m', strtotime($date));

        $mapArray = [
            1 => 1,
            2 => 1,
            3 => 1,
            4 => 2,
            5 => 2,
            6 => 2,
            7 => 3,
            8 => 3,
            9 => 3,
            10 => 4,
            11 => 4,
            12 => 4,
        ];

        return $mapArray[$m];
    }

    /**
     * @param string $time
     * @return string
     */
    public static function datumUah(string $time = 'now'): string
    {
        $time = strtotime($time);

        $date = date('"d" F Y', $time) .' p.';

        $men = [
            'January', 'February', 'March', 'April', 'May',
            'June', 'July', 'August', 'September', 'October',
            'November', 'December'
        ];

        $mcz = [
            'січня', 'лютого', 'березня', 'квітня', 'травня',
            'червня', 'липня', 'серпня', 'вересня', 'жовтня',
            'листопада', 'грудня'
        ];

        return str_replace($men, $mcz, $date);
    }

    public static function datumUah2(string $time = 'now'): string
    {
        $time = strtotime($time);

        $date = date('F Y', $time);

        $men = [
            'January', 'February', 'March', 'April', 'May',
            'June', 'July', 'August', 'September', 'October',
            'November', 'December'
        ];

        $mcz = [
            'Січ.', 'Лют.', 'Бер.', 'Квіт.', 'Трав.',
            'Черв.', 'Лип.', 'Серп.', 'Верес.', 'Жовт.',
            'Лист.', 'Груд.'
        ];

        return str_replace($men, $mcz, $date);
    }

    public static function getQuarterDate(int $quarter, int $year): array
    {
        switch ($quarter) {
            case 1 :
                return [
                    'start' => '01.01.'.$year,
                    'end' => '31.03.'.$year,
                ];
            case 2 :
                return [
                    'start' => '01.04.'.$year,
                    'end' => '30.06.'.$year,
                ];
            case 3 :
                return [
                    'start' => '01.07.'.$year,
                    'end' => '30.09.'.$year,
                ];
            case 4 :
                return [
                    'start' => '01.10.'.$year,
                    'end' => '31.12.'.$year,
                ];
        }

        return [
            'start' => '01.01.'.$year,
            'end' => '31.03.'.$year,
        ];
    }
}