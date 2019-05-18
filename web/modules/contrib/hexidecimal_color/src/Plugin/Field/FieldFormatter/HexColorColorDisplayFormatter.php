<?php

namespace Drupal\hexidecimal_color\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Colored block formatter for (Hexidecimal) Color fields.
 *
 * @FieldFormatter(
 *   id = "hexidecimal_color_color_display",
 *   label = @Translation("Colored Block"),
 *
 *   field_types = {
 *      "hexidecimal_color"
 *   }
 * )
 */
class HexColorColorDisplayFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = [];

    $summary['overview'] = $this->t('Displays the color in a colored block/square/div');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#theme' => 'hexidecimal_color_color_display',
        '#entity_delta' => $delta,
        '#item' => $item,
        '#color' => $item->get('color')->getValue(),
      ];
    }

    return $element;
  }

}
