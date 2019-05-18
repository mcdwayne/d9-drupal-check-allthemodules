<?php

namespace Drupal\persian_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Plugin implementation of the 'melli_code_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "sheba_formatter",
 *   label = @Translation("Sheba formatter"),
 *   field_types = {
 *     "sheba"
 *   }
 * )
 */
class ShebaFormatter extends BasePersianFormatter {

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
