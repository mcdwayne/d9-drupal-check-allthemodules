<?php

namespace Drupal\hexidecimal_color\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Raw RGB formatter for (Hexidecimal) Color fields.
 *
 * @FieldFormatter(
 *   id = "hexidecimal_color_raw_rgb_display",
 *   label = @Translation("Raw RGB"),
 *
 *   field_types = {
 *      "hexidecimal_color"
 *   }
 * )
 */
class HexColorRawRgbDisplayFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary['overview'] = $this->t('Displays a RGB representation of the color, with no HTML wrappers.');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#markup' => $this->hexToRgb($item->value),
      ];
    }

    return $element;
  }

  /**
   * Helper function to convert hex to rgb.
   */
  private function hexToRgb($hex) {
    $hex = str_replace("#", "", $hex);

    if (strlen($hex) == 3) {
      $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
      $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
      $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    }
    else {
      $r = hexdec(substr($hex, 0, 2));
      $g = hexdec(substr($hex, 2, 2));
      $b = hexdec(substr($hex, 4, 2));
    }

    $rgb = [$r, $g, $b];

    // Returns the rgb values separated by commas.
    return implode(",", $rgb);
  }

}
