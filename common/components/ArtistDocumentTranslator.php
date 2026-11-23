<?php

namespace common\components;

use common\models\Lang;
use common\models\Message;
use backend\models\Artist;
use backend\models\SubLabel;

class ArtistDocumentTranslator
{
    private static array $cache = [];

    public static function language(Artist|SubLabel $entity): Lang
    {
        $language = $entity->lang;
        if (!$language instanceof Lang) {
            $language = Lang::find()->where(['default' => 1])->one();
        }

        if (!$language instanceof Lang) {
            $language = Lang::find()->orderBy(['id' => SORT_ASC])->one();
        }

        if (!$language instanceof Lang) {
            throw new \RuntimeException('У базі даних не налаштовано жодної мови.');
        }

        return $language;
    }

    public static function applyLanguage(Artist|SubLabel $entity): void
    {
        \Yii::$app->language = self::language($entity)->local;
    }

    public static function t(Artist|SubLabel $entity, string $source, array $params = []): string
    {
        $language = self::language($entity);
        $key = $language->id . '\0' . $source;

        if (!array_key_exists($key, self::$cache)) {
            $message = Message::find()->where([
                'lang_id' => $language->id,
                'name' => $source,
            ])->one();

            if ($message instanceof Message) {
                self::$cache[$key] = $message->translate;
            } else {
                // Missing entries are stored in the database for later translation.
                $message = new Message();
                $message->lang_id = $language->id;
                $message->name = $source;
                $message->translate = $source;
                $message->save(false);
                self::$cache[$key] = $source;
            }
        }

        $text = self::$cache[$key];
        foreach ($params as $name => $value) {
            $text = str_replace('{' . $name . '}', (string)$value, $text);
        }

        return $text;
    }

    public static function languageCode(Artist|SubLabel $entity): string
    {
        $language = self::language($entity);
        $code = strtolower(trim((string)($language->url ?: $language->local)));
        $code = preg_split('/[-_]/', $code)[0] ?: 'uk';
        $code = preg_replace('/[^a-z0-9]/', '', $code);

        return $code !== '' ? $code : 'uk';
    }
}
