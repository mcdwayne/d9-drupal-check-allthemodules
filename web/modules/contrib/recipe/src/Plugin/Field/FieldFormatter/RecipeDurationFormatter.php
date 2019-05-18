<?php

namespace Drupal\recipe\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'ingredient_default' formatter.
 *
 * @FieldFormatter(
 *   id = "recipe_duration",
 *   module = "recipe",
 *   label = @Translation("Recipe duration"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class RecipeDurationFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#theme' => 'recipe_duration',
        '#duration' => $item->value,
      ];
    }
    return $elements;
  }

}
