<?php

namespace Drupal\ivw_integration\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'ivw_empty_formatter' formatter.
 *
 * This is necessary, since we cannot define a field without a field formatter,
 * even if the field should not output anything.
 *
 * @FieldFormatter(
 *   id = "ivw_empty_formatter",
 *   module = "ivw_integration",
 *   label = @Translation("Empty formatter"),
 *   field_types = {
 *     "ivw_integration_settings"
 *   }
 * )
 */
class IvwEmptyFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {
    // Does not actually output anything.
    return [];
  }

}
