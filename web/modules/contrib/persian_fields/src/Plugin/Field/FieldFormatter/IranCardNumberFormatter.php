<?php

namespace Drupal\persian_fields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Plugin implementation of the 'melli_code_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "iran_card_number_formatter",
 *   label = @Translation("Iran payment card number formatter"),
 *   field_types = {
 *     "iran_card_number"
 *   }
 * )
 */
class IranCardNumberFormatter extends BasePersianFormatter {

  /**
   * @inheritdoc
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return sprintf("%s %s %s %s",
      substr($item->value, 0, 4),
      substr($item->value, 4, 4),
      substr($item->value, 8, 4),
      substr($item->value, 12, 4)
    );
  }

}
