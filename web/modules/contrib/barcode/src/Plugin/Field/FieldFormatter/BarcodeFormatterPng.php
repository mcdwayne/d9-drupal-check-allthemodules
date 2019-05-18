<?php

namespace Drupal\barcode\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the barcode PNG formatter.
 *
 * @FieldFormatter(
 *   id = "barcode_png",
 *   label = @Translation("Barcode PNG"),
 *   field_types = {
 *      "barcode",
 *      "barcode_matrix"
 *    },
 * )
 */
class BarcodeFormatterPng extends BarcodeFormatterBase {

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
          '#type' => 'html_tag',
          '#tag' => 'img',
          '#attributes' => [
            'alt' => $item->types[$item->type]['name'] . ': ' . $item->value,
            'src' => 'data:image/png;base64, ' . base64_encode($barcode->getPngData()),
          ],
        ],
      ];
    }

    return $element;
  }

}
