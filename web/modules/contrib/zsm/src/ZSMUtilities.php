<?php

/**
 * @file
 */

namespace Drupal\zsm;

/**
 * Class ZSMUtilities
 * @package Drupal\zsm
 *
 * Creates a set of common functions used by plugins
 */
class ZSMUtilities {

  /**
   * Handles digest of alert threshold fields
   */
  public static function digestAlertThresholdField($val) {
    $ret = array();
    foreach ($val as $item) {
      if (isset($item['context']) && isset($item['amount']) && isset($item['severity'])) {
        $r = array();
        $r['type'] = $item['type'];
        $item['context'] ? $r['server_family'] = $item['context'] : NULL;
        $item['amount'] ? $r['amount'] = $item['amount'] : NULL;
        if($item['severity'] === 'custom') {
          $r['severity'] = $item['severity_custom'] ? $item['severity_custom'] : 'notice';
        }
        else {
          $r['severity'] = $item['severity'];
        }
        $ret[] = $r;
      }
    }
    return $ret;
  }

  /**
   * Handles digest of section-list fields
   */
  public static function digestSectionListField($val) {
    $ret = array();
    foreach ($val as $item) {
      $list = explode(PHP_EOL, $item['list']);
      $list_processed = array();
      if (strpos($item['list'], '|') !== FALSE) {
        foreach ($list as $listitem) {
          $li = explode('|', $listitem);
          if (count($li) >= 2) {
            $list_processed[trim($li[0])] = trim($li[1]);
          }
        }
        $ret[$item['section']] = $list_processed;
      }
      else {
        foreach ($list as $listitem) {
          $ret[$item['section']][] = trim($listitem);
        }
      }
    }
    return $ret;
  }

  /**
   * Handles digest of regex fields
   */
  public static function digestRegExPatternField($val) {
    $ret = array();
    foreach ($val as $item) {
      if (
        isset($item['type']) &&
        isset($item['location']) &&
        isset($item['pattern'])
      ) {
        $ret[$item['type']] = array(
          'location' => $item['location'],
          'pattern' => $item['pattern'],
        );
      }
    }
    return $ret;
  }

}