<?php

/**
 * @file
 * Contains \Drupal\field_formatters\Plugin\Field\FieldFormatter.
 */

namespace Drupal\field_formatters\Plugin\Field\FieldFormatter;


use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'Rot13Coding' formatter.
 *
 * @FieldFormatter(
 *   id = "rot13coding",
 *   label = @Translation("Apply coding ROT13"),
 *   field_types = {
 *     "string", "list_string", "text_with_summary", "text_long",
       "string_long", "text"
 *   }
 * )
 */

class FormatterRot13Coding extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      // Render each element as markup.
      $element[$delta] = [
        '#type' => 'markup',
        '#markup' => _field_formatters_rot13_coding($item->value),
      ];
    }

    return $element;
  }
}


