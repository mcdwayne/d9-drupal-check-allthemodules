<?php

namespace Drupal\datex\Datex;

final class DatexPersianIntlCalendar extends DatexIntlCalendar {

  function validate(array $arr) {
    if ((!isset($arr['year']) || empty($arr['year'])) &&
      (!isset($arr['month']) || empty($arr['month'])) &&
      (!isset($arr['day']) || empty($arr['day']))) {
      return NULL;
    }
    $zero = TRUE;
    $year = intval($arr['year']);
    $month = intval($arr['month']);
    $day = intval($arr['day']);
    if ($year < 0 || $year === 0 && $zero) {
      return t('Year out of range');
    }
    if ($month < 0 || 12 < $month || $month === 0 && $zero) {
      return t('Month out of range');
    }
    if ($day === 0 && $zero || $day < 0 || 31 < $day || $month > 6 && $day > 30 || $month === 12 && $day > 29) {
      return t('Day out of range');
    }
    return FALSE;
  }

  function copy() {
    return new DatexPersianIntlCalendar($this->timezone, $this->calendar, $this->langCode);
  }

  protected function formatHook($format, $value) {
    $characters = [
      '۰' => '0',
      '۱' => '1',
      '۲' => '2',
      '۳' => '3',
      '۴' => '4',
      '۵' => '5',
      '۶' => '6',
      '۷' => '7',
      '۸' => '8',
      '۹' => '9',
      '٠' => '0',
      '١' => '1',
      '٢' => '2',
      '٣' => '3',
      '٤' => '4',
      '٥' => '5',
      '٦' => '6',
      '٧' => '7',
      '٨' => '8',
      '٩' => '9',
    ];
    return strtr($value, $characters);
  }

  function getBaseYear() {
    return 1390;
  }

}
