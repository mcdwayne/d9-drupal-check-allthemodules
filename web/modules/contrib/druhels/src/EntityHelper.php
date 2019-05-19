<?php

namespace Drupal\druhels;

use Drupal\Core\Field\FieldItemListInterface;

class EntityHelper {

  /**
   * Return value label for List field.
   */
  public static function getListFieldValueLabel(FieldItemListInterface $field, $delta = 0) {
    $allowed_values = $field->getSetting('allowed_values');
    $value = $field->value;
    return $value !== NULL && isset($allowed_values[$value]) ? $allowed_values[$value] : $value;
  }

}
