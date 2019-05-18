<?php

namespace Drupal\responsive_class_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'responsive_class_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "responsive_class_formatter",
 *   module = "responsive_class_field",
 *   label = @Translation("Responsive class"),
 *   field_types = {
 *     "responsive_class"
 *   }
 * )
 */
class ResponsiveClassFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Does not actually output anything.
    return [];
  }

}
