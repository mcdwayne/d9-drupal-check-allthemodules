<?php

namespace Drupal\onlinepbx\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Period Controller.
 */
class Period extends ControllerBase {

  /**
   * Today params.
   */
  public static function getToDayParams() {
    $params = [
      'start' => strtotime('today'),
      'end' => strtotime("tomorrow"),
    ];
    if ($day = \Drupal::request()->query->get('day')) {
      if (is_numeric($tstamp = strtotime($day))) {
        $start = format_date($tstamp, 'custom', 'Y-m-d');
        $params = [
          'start' => strtotime($start),
          'end' => strtotime("$start +1day"),
        ];
      }
    }
    return $params;
  }

  /**
   * Days.
   */
  public static function days($start, $end) {
    $days = [];
    $start = new \DateTime(format_date($start, 'custom', 'Y-m-d'));
    $end = new \DateTime(format_date($end, 'custom', 'Y-m-d'));
    $interval = \DateInterval::createFromDateString('1 day');
    $period = new \DatePeriod($start, $interval, $end);

    foreach ($period as $dt) {
      $dt = DrupalDateTime::createFromTimestamp($dt->getTimestamp());
      $key = $dt->format("d-m-Y");
      $days[$key] = [
        'day' => $dt->format("d M"),
        'picup' => 0,
        'calls' => 0,
        'xname' => '',
      ];
    }
    return $days;
  }

  /**
   * Times.
   */
  public static function dayTime() {
    $time = [
      ' 8.00' => 0,
      ' 8.30' => 0,
      ' 9.00' => 0,
      ' 9.30' => 0,
      '10.00' => 0,
      '10.30' => 0,
      '11.00' => 0,
      '11.30' => 0,
      '12.00' => 0,
      '12.30' => 0,
      '13.00' => 0,
      '13.30' => 0,
      '14.00' => 0,
      '14.30' => 0,
      '15.00' => 0,
      '15.30' => 0,
      '16.00' => 0,
      '16.30' => 0,
      '16.00' => 0,
      '16.30' => 0,
      '17.00' => 0,
      '17.30' => 0,
      '18.00' => 0,
      '18.30' => 0,
    ];
    return $time;
  }

  /**
   * Period.
   */
  public static function timePeriod($timestamp) {
    $hours = format_date($timestamp, 'custom', 'H');
    $minuts = format_date($timestamp, 'custom', 'i');
    if ($minuts < 30) {
      $minuts = '00';
    }
    else {
      $minuts = '30';
    }
    if ($hours <= 8) {
      $hours = ' 8';
    }
    elseif ($hours == 9) {
      $hours = ' 9';
    }
    elseif ($hours > 18) {
      $hours = '18';
      $minuts = '30';
    }
    $time = "$hours.$minuts";
    return $time;
  }

}
