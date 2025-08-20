<?php

namespace common\models;

use yii\base\Model;

/**
 * Telegram send message
 */
class t extends Model
{
    private const CHATID = 404070580; // Романчук Ярослав

    /**
     * Send to me
     *
     * @param string $message
     * @param int|null $chat_id
     * @return void
     */
    public static function log(string $message, ?int $chat_id = null): void
    {
        if (is_null($chat_id)) {
            $chat_id = self::CHATID;
        }

         self::send($chat_id, $message);
    }

    /**
     * Send to me
     *
     * @param int $chat_id
     * @param $text
     * @return void
     */
    public static function message(int $chat_id, $text)
    {
        if (is_string($text)) {
            $message = $text;
        } else if (is_array($text)) {
            $message = json_encode($text);
        }

        self::send($chat_id, $message);
    }

    /**
     * @param int $chat_id
     * @param string $text
     * @return void
     */
    private static function send(int $chat_id, string $text)
    {
        @file_get_contents('https://api.telegram.org/bot6852397575:AAFGAmEcK9ffixDloNhf6sT6BrsjmJ0CkaQ/sendMessage?chat_id=' . $chat_id . '&text=' .urlencode($text)).'&parse_mode=HTML';
    }
}