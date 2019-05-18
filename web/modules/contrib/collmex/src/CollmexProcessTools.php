<?php

namespace Drupal\collmex;

use Drupal\Core\Datetime\DrupalDateTime;

class CollmexProcessTools {

  public static function firstOfMonth($value) {
    $value = (new DrupalDateTime($value))->format('Y-m-01');
    return $value;
  }

}
