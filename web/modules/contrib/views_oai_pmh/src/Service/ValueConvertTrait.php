<?php

namespace Drupal\views_oai_pmh\Service;

/**
 *
 */
trait ValueConvertTrait {

  /**
   *
   */
  public function __construct() {

  }

  /**
   *
   */
  public function convert($currentValue, $newValue) {
    $output = [];
    if (is_array($currentValue)) {
      foreach ($currentValue as $id => $value) {
        $output[] = $value;
      }
    }
    else {
      $output[] = $currentValue;
    }

    $output[] = $newValue;

    return $output;
  }

}
