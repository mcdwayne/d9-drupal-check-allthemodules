<?php

namespace Drupal\scss_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the SCSS formatter.
 *
 * @FieldFormatter(
 *   id = "scss",
 *   label = @Translation("Random text"),
 *   field_types = {
 *     "scss"
 *   }
 * )
 */
class ScssFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      // Render each element as markup.
      $element[$delta] = [
        '#markup' => $item->value,
      ];
    }
    $element['quickedit'] = ['editor' => 'editor'];
    return $element;
  }

}
