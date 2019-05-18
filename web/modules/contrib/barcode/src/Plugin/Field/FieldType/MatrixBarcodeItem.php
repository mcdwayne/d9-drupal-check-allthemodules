<?php

namespace Drupal\barcode\Plugin\Field\FieldType;

/**
 * Defines the 'barcode_matrix' field type.
 *
 * @FieldType(
 *   id = "barcode_matrix",
 *   label = @Translation("Matrix Barcode"),
 *   category = @Translation("General"),
 *   default_widget = "barcode",
 *   default_formatter = "barcode_png"
 * )
 */
class MatrixBarcodeItem extends BarcodeBase {

  /**
   * {@inheritdoc}
   */
  public static $types = [
    'DATAMATRIX' => [
      'name' => 'DATAMATRIX (ISO/IEC 16022)',
      'length' => 255,
      'type' => 'mixed',
      'placeholder' => '',
      'description' => '',
    ],
    'PDF417' => [
      'name' => 'PDF417 (ISO/IEC 15438:2006)',
      'length' => 255,
      'type' => 'mixed',
      'placeholder' => '',
      'description' => '',
    ],
    'SRAW' => [
      'name' => '2D RAW MODE (comma-separated rows of 01 strings)',
      'length' => 255,
      'type' => 'mixed',
      'placeholder' => '',
      'description' => '',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public static function standardBarcodes() {
    $barcodes = [];
    foreach ($types as $code) {
      $barcodes[$code] = static::$types[$code]['name'];
    }
    return $barcodes;
  }

}
