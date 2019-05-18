<?php

namespace Drupal\entitytools;

use Drupal\Core\Field\FieldItemListInterface;

class DateFieldHelper {

  public static function set(FieldItemListInterface $fieldItem) {
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter */
    $dateFormatter = \Drupal::service('date.formatter');
    $fieldItem->set(0, $dateFormatter->format(
      \Drupal::time()->getRequestTime(), 'custom', 'Y-m-d'));
  }

  public static function clear(FieldItemListInterface $fieldItem) {
    $fieldItem->set(0, NULL);
  }

}
