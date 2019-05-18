<?php

namespace Drupal\persian_date\Service\Translation;

class OffsetFilterTranslator
{
    /**
     * @param $text string Text to translate
     * @return string Translated text
     */
    public static function translate($text)
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));
        $text = self::standardPersianInput($text);
        $translations = self::translations();
        foreach ($translations['strict'] as $english_word => $persian_words) {
            foreach ($persian_words as $persian_word) {
                if ($text === $persian_word) {
                    $text = str_replace($persian_word, $english_word, $text);
                }
            }
        }
        foreach ($translations['relative'] as $english_word => $persian_words) {
            foreach ($persian_words as $persian_word) {
                $text = str_replace($persian_word, $english_word, $text);
            }
        }
        return $text;
    }

    private static function standardPersianInput($string)
    {
        $characters = [
            'ك' => 'ک',
            'دِ' => 'د',
            'بِ' => 'ب',
            'زِ' => 'ز',
            'ذِ' => 'ذ',
            'شِ' => 'ش',
            'سِ' => 'س',
            'ى' => 'ی',
            'ي' => 'ی',
            '١' => '۱',
            '٢' => '۲',
            '٣' => '۳',
            '٤' => '۴',
            '٥' => '۵',
            '٦' => '۶',
            '٧' => '۷',
            '٨' => '۸',
            '٩' => '۹',
            '٠' => '۰',
        ];

        $string = str_replace(array_keys($characters), array_values($characters), $string);

        $characters = [
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
            '۰' => '0',
        ];

        return str_replace(array_keys($characters), array_values($characters), $string);
    }

    private static function translations()
    {
        return [
            'strict' => [
                'today' => ['امروز'],
                'last day' => ['روز قبل', 'روز پیش'],
                'next day' => ['روز بعد', 'روز آینده'],
                'last week' => ['هفته قبل', 'هفته پیش'],
                'next week' => ['هفته بعد', 'هفته آینده'],
                'last month' => ['ماه قبل', 'ماه پیش'],
                'next month' => ['ماه بعد', 'ماه آینده'],
                'last year' => ['سال قبل', 'سال پیش'],
                'next year' => ['سال بعد', 'سال آینده'],
            ],
            'relative' => [
                'day ago' => ['روز قبل', 'روز پیش'],
                'day later' => ['روز بعد', 'روز آینده'],
                'week ago' => ['هفته قبل', 'هفته پیش'],
                'week later' => ['هفته بعد', 'هفته آینده'],
                'month ago' => ['ماه قبل', 'ماه پیش'],
                'month later' => ['ماه بعد', 'ماه آینده'],
                'year ago' => ['سال قبل', 'سال پیش'],
                'year later' => ['سال بعد', 'سال آینده'],
                'tomorrow' => ['فردا'],
                'yesterday' => ['دیروز'],
                'second' => ['ثانیه'],
                'minute' => ['دقیقه'],
                'hour' => ['ساعت'],
                'day' => ['روز'],
                'week' => ['هفته'],
                'month' => ['ماه'],
                'year' => ['سال'],
            ]
        ];
    }
}