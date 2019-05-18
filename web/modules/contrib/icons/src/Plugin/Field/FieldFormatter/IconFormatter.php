<?php

namespace Drupal\icons\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'list_default' formatter.
 *
 * @FieldFormatter(
 *   id = "list_icon",
 *   label = @Translation("Icon"),
 *   field_types = {
 *     "list_icon",
 *   }
 * )
 */
class IconFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if (!$item->isEmpty()) {
        $value = explode(':', $item->value);
        if (count($value) > 1) {
          list($icon_set, $icon_name) = $value;
          $elements[$delta] = [
            '#type' => 'icon',
            '#icon_set' => $icon_set,
            '#icon_name' => $icon_name,
          ];
        }
      }
    }

    return $elements;
  }

}
