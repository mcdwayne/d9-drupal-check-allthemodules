<?php

namespace Drupal\persian_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Plugin implementation of the 'melli_code_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "melli_code_formatter",
 *   label = @Translation("Melli code formatter"),
 *   field_types = {
 *     "melli_code"
 *   }
 * )
 */
class MelliCodeFormatter extends BasePersianFormatter {

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
    return sprintf("%s %s %s",
      substr($item->value, 0, 4),
      substr($item->value, 4, 3),
      substr($item->value, 7, 3)
    );
  }

}
