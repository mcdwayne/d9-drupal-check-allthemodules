<?php

namespace Drupal\barcode\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Markup;

/**
 * Plugin implementation of the barcode SVG Div formatter.
 *
 * @FieldFormatter(
 *   id = "barcode_svg",
 *   label = @Translation("Barcode SVG"),
 *   field_types = {
 *      "barcode",
 *      "barcode_matrix"
 *    },
 * )
 */
class BarcodeFormatterSvg extends BarcodeFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $settings = $this->getSettings();
    $background = ($settings['background']) ? '#' . $settings['background'] : '#FFFFFF';
    $foreground = ($settings['foreground']) ? '#' . $settings['foreground'] : '#000000';

    foreach ($items as $delta => $item) {
      $barcode = $this->generateBarcode($item, $background, $foreground);
      $element[$delta] = [
        '#theme' => 'barcode',
        '#data' => $item,
        '#barcode' => [
          '#markup' => Markup::create($barcode->getSvgCode()),
        ],
      ];
    }

    return $element;
  }

}
