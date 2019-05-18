<?php

namespace Drupal\barcode\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Markup;

/**
 * Plugin implementation of the barcode HTML Div formatter.
 *
 * @FieldFormatter(
 *   id = "barcode_html",
 *   label = @Translation("Barcode HTML"),
 *   field_types = {
 *      "barcode",
 *      "barcode_matrix"
 *    },
 * )
 */
class BarcodeFormatterHtml extends BarcodeFormatterBase {

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
          '#markup' => Markup::create($barcode->getHtmlDiv()),
        ],
      ];
    }

    return $element;
  }

}
