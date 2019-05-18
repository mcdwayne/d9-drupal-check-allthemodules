<?php

namespace Drupal\pluggable\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'pluggable_default' formatter.
 *
 * @FieldFormatter(
 *   id = "pluggable_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "pluggable_item"
 *   }
 * )
 */
class PluggableDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $target_definition = $item->getTargetDefinition();
      if (!empty($target_definition['label'])) {
        $elements[$delta] = [
          '#markup' => $target_definition['label'],
        ];
      }
      else {
        $elements[$delta] = [
          '#markup' => $target_definition['id'],
        ];
      }
    }

    return $elements;
  }

}
