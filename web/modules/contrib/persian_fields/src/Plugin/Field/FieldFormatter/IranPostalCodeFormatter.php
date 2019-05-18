<?php

namespace Drupal\persian_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Plugin implementation of the 'melli_code_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "iran_postal_code_formatter",
 *   label = @Translation("Iran postal code formatter"),
 *   field_types = {
 *     "iran_postal_code"
 *   }
 * )
 */
class IranPostalCodeFormatter extends BasePersianFormatter {

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    return $item->value;
  }

}
