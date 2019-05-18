<?php

namespace Drupal\no_nbsp\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'no_nbsp' formatter.
 *
 * @FieldFormatter(
 *   id = "no_nbsp",
 *   label = @Translation("No Non-breaking Space Filter"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *   }
 * )
 */
class NoNbspFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#markup' => _no_nbsp_eraser($item->value),
      ];
    }

    return $element;
  }

}
