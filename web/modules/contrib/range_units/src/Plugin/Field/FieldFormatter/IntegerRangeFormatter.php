<?php

namespace Drupal\range_units\Plugin\Field\FieldFormatter;

/**
 * Plugin implementation of the 'integer_range' formatter.
 *
 * @FieldFormatter(
 *   id = "integer_range_formatter",
 *   label = @Translation("Integer Range"),
 *   field_types = {
 *     "integer_range"
 *   }
 * )
 */
class IntegerRangeFormatter extends RangeFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected function numberFormat($number) {
    return number_format($number, 0, '', $this->getSetting('thousand_separator'));
  }

}
