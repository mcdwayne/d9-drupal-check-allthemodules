<?php

namespace Drupal\datex\Plugin\views\argument;

trait DatexArgHandlerTrait {

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $cal = datex_factory();
    if ($cal && $cal->getCalendarName() !== 'gregorian') {
      if ($cal->parse(self::translate($this->argument), $this->argFormat)) {
        $this->argument = $cal->xFormat($this->argFormat);
      }
    }
    parent::query($group_by);
  }

  public static function translate($v) {
    $pass_0 = [
      'ﻯ' => 'ی',
      'ﻱ' => 'ی',
      '٠' => '0',
      '۱' => '1',
      '۲' => '2',
      '۳' => '3',
      '۴' => '4',
      '۵' => '5',
      '۶' => '6',
      '۷' => '7',
      '۸' => '8',
      '۹' => '9',
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
    $pass_1 = [
      'امروز' => 'today',
      'دیروز' => '-1 day',
      'فردا' => '+1 day',
      'پیش' => 'ago',
      'قبل' => 'last',
      'بعد' => 'later',
      'بعدی' => 'next',
      'ثانیه' => 'second',
      'دقیقه' => 'minute',
      'ساعت' => 'hour',
      'روز' => 'day',
      'ماه' => 'month',
      'سال' => 'year',
      'هفته' => 'week',
    ];
    $v = trim($v);
    $v = strtr($v, $pass_0);
    $v = strtr($v, $pass_1);
    return $v;
  }


}
