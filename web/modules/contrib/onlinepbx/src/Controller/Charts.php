<?php

namespace Drupal\onlinepbx\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Charts Controller.
 */
class Charts extends ControllerBase {

  /**
   * Charts Data.
   */
  public static function makeData($data = [], $display = 'user') {
    $collsrc = $data['users'];
    $collsrckey = 'user';
    if ($dislpay = \Drupal::request()->query->get('display')) {
      $collsrc = $data['gateways'];
      $collsrckey = 'gw';
    }

    // Calls reStruct: user->day->calls.
    $hcalls = [];
    foreach ($data['calls'] as $call) {
      $date = format_date($call['date'], 'custom', 'd-m-Y');
      $hkey = $call[$collsrckey];
      $hcalls[$hkey][$date][] = $call;
    }
    // Header.
    $header = [''];
    foreach ($collsrc as $col) {
      $header[] = $col['xname'];
    }
    $charts = [$header];
    // Rows.
    foreach ($data['days'] as $d => $day) {
      // 1st col.
      $row = [$day['day']];
      foreach ($collsrc as $k => $unused) {
        // Count calls.
        $row[] = isset($hcalls[$k][$d]) ? count($hcalls[$k][$d]) : 0;
      }
      $charts[] = $row;
    }

    return $charts;
  }

  /**
   * Charts Data.
   */
  public static function makeDayPulse($data) {
    $collsrc = $data['users'];
    $collsrckey = 'user';

    // Calls reStruct: user->day->calls.
    $hcalls = [];
    foreach ($data['calls'] as $call) {
      $date = $call['time'];
      $hkey = $call[$collsrckey];
      $hcalls[$hkey][$date][] = $call;
    }
    // Header.
    $header = [''];
    foreach ($collsrc as $col) {
      $header[] = $col['xname'];
    }
    $charts = [$header];
    // Rows.
    foreach ($data['time'] as $d => $t) {
      // 1st col.
      $row = [$d];
      foreach ($collsrc as $k => $user) {
        // Count calls.
        $row[] = isset($hcalls[$k][$d]) ? count($hcalls[$k][$d]) : 0;
      }
      $charts[] = $row;
    }
    return $charts;
  }

}
