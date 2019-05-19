<?php

namespace Drupal\xhprof\XHProfLib\Parser;

/**
 * Class SymbolsSorter
 */
class SymbolsSorter {

  private static $metric;

  /**
   * @param $symbols
   * @param $metric
   */
  static function sort(&$symbols, $metric) {
    self::$metric = $metric;
    uasort($symbols, array(
        "Drupal\\xhprof\\XHProfLib\\Parser\\SymbolsSorter",
        "cmp_method"
      ));
  }

  /**
   * @param $a
   * @param $b
   *
   * @return int
   */
  static function cmp_method($a, $b) {
    $metric = self::$metric;

    if ($metric == "fn") {

      // case insensitive ascending sort for function names
      $left = strtoupper($a["fn"]);
      $right = strtoupper($b["fn"]);

      if ($left == $right) {
        return 0;
      }

      return ($left < $right) ? -1 : 1;
    }
    else {
      // descending sort for all others
      $left = $a[$metric];
      $right = $b[$metric];

      if ($left == $right) {
        return 0;
      }
      return ($left > $right) ? -1 : 1;
    }
  }

}
