<?php

namespace Drupal\datex\Controller;

class DatexConfigController {

  public function config() {
    $element['#type'] = 'markup';
    $element['#markup'] = 'hello';
    $config = \Drupal::config('datex.schemas');

    return $element;
  }

}
