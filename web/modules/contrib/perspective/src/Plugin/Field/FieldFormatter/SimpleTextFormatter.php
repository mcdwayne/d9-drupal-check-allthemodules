<?php

namespace Drupal\perspective\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'perspective_formatted_text' formatter.
 *
 * @FieldFormatter(
 *   id = "perspective_formatted_text",
 *   module = "perspective",
 *   label = @Translation("Simple text-based formatter"),
 *   field_types = {
 *     "perspective"
 *   }
 * )
 */
class SimpleTextFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => $item->value,
      ];
    }

    return $elements;
  }

}
