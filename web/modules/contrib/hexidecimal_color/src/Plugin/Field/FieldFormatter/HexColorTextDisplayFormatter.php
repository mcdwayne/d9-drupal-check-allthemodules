<?php

namespace Drupal\hexidecimal_color\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Text display formatter for (Hexidecimal) Color fields.
 *
 * @FieldFormatter(
 *   id = "hexidecimal_color_text_display",
 *   label = @Translation("Text"),
 *
 *   field_types = {
 *      "hexidecimal_color"
 *   }
 * )
 */
class HexColorTextDisplayFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary['overview'] = $this->t('Displays the textual representation of the color');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#theme' => 'hexidecimal_color_text_display',
        '#entity_delta' => $delta,
        '#item' => $item,
        '#color' => $item->get('color')->getValue(),
      ];
    }

    return $element;
  }

}
