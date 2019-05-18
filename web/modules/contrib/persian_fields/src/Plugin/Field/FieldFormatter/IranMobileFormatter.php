<?php

namespace Drupal\persian_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Plugin implementation of the 'melli_code_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "iran_mobile_formatter",
 *   label = @Translation("Iran mobile formatter"),
 *   field_types = {
 *     "iran_mobile"
 *   }
 * )
 */
class IranMobileFormatter extends BasePersianFormatter {

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
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return $item->value;
  }

}
