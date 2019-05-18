<?php

namespace Drupal\barcode\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'barcode' field type.
 *
 * @FieldType(
 *   id = "barcode",
 *   label = @Translation("Barcode"),
 *   category = @Translation("General"),
 *   default_widget = "barcode",
 *   default_formatter = "barcode_png"
 * )
 */
class BarcodeItem extends BarcodeBase {

  /**
   * {@inheritdoc}
   */
  public static $types = [
    'C128A' => [
      'name' => 'CODE 128 A',
      'length' => 255,
      'type' => 'mixed',
      'placeholder' => '',
      'description' => '',
    ],
    'C128B' => [
      'name' => 'CODE 128 B',
      'length' => 255,
      'type' => 'mixed',
      'placeholder' => '',
      'description' => '',
    ],
    'C128C' => [
      'name' => 'CODE 128 C',
      'length' => 255,
      'type' => 'mixed',
      'placeholder' => '',
      'description' => '',
    ],
    'C128' => [
      'name' => 'CODE 128',
      'length' => 255,
      'type' => 'mixed',
      'placeholder' => '',
      'description' => '',
    ],
    'C39E+' => [
      'name' => 'CODE 39 EXTENDED + CHECKSUM',
      'length' => 255,
      'type' => 'mixed',
      'placeholder' => '',
      'description' => '',
    ],
    'C39E' => [
      'name' => 'CODE 39 EXTENDED',
      'length' => 255,
      'type' => 'mixed',
      'placeholder' => '',
      'description' => '',
    ],
    'C39+' => [
      'name' => 'CODE 39 + CHECKSUM',
      'length' => 255,
      'type' => 'mixed',
      'placeholder' => '',
      'description' => '',
    ],
    'C39' => [
      'name' => 'CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9.',
      'length' => 255,
      'type' => 'mixed',
      'placeholder' => '',
      'description' => '',
    ],
    'C93' => [
      'name' => 'CODE 93 - USS-93',
      'length' => 255,
      'type' => 'mixed',
      'placeholder' => '',
      'description' => '',
    ],
    'CODABAR' => [
      'name' => 'CODABAR',
      'length' => 255,
      'type' => 'numeric',
      'placeholder' => '',
      'description' => '',
    ],
    'CODE11' => [
      'name' => 'CODE 11',
      'length' => 255,
      'type' => 'mixed',
      'placeholder' => '',
      'description' => '',
    ],
    'EAN13' => [
      'name' => 'EAN 13',
      'length' => 13,
      'type' => 'numeric',
      'placeholder' => '',
      'description' => '',
    ],
    'EAN2' => [
      'name' => 'EAN 2-Digits UPC-Based Extension',
      'length' => 2,
      'type' => 'numeric',
      'placeholder' => '01',
      'description' => '',
    ],
    'EAN5' => [
      'name' => 'EAN 5-Digits UPC-Based Extension',
      'length' => 5,
      'type' => 'numeric',
      'placeholder' => '01234',
      'description' => '',
    ],
    'EAN8' => [
      'name' => 'EAN 8',
      'length' => 8,
      'type' => 'numeric',
      'placeholder' => '01234567',
      'description' => '',
    ],
    'I25+' => [
      'name' => 'Interleaved 2 of 5 + CHECKSUM',
      'length' => 255,
      'type' => 'numeric',
      'placeholder' => '',
      'description' => '',
    ],
    'I25' => [
      'name' => 'Interleaved 2 of 5',
      'length' => 255,
      'type' => 'numeric',
      'placeholder' => '',
      'description' => '',
    ],
    'IMB' => [
      'name' => 'IMB - Intelligent Mail Barcode - Onecode - USPS-B-3200',
      'length' => 255,
      'type' => 'numeric',
      'placeholder' => '',
      'description' => '',
    ],
    'IMBPRE' => [
      'name' => 'IMB - Intelligent Mail Barcode pre-processed',
      'length' => 255,
      'type' => 'numeric',
      'placeholder' => '',
      'description' => '',
    ],
    'KIX' => [
      'name' => 'KIX (Klant index - Customer index)',
      'length' => 255,
      'type' => 'mixed',
      'placeholder' => '',
      'description' => '',
    ],
    'MSI+' => [
      'name' => 'MSI + CHECKSUM (modulo 11)',
      'length' => 255,
      'type' => 'numeric',
      'placeholder' => '',
      'description' => '',
    ],
    'MSI' => [
      'name' => 'MSI (Variation of Plessey code)',
      'length' => 255,
      'type' => 'numeric',
      'placeholder' => '',
      'description' => '',
    ],
    'PHARMA2T' => [
      'name' => 'PHARMACODE TWO-TRACKS',
      'length' => 255,
      'type' => 'numeric',
      'placeholder' => '',
      'description' => '',
    ],
    'PHARMA' => [
      'name' => 'PHARMACODE',
      'length' => 255,
      'type' => 'numeric',
      'placeholder' => '',
      'description' => '',
    ],
    'PLANET' => [
      'name' => 'PLANET',
      'length' => 14,
      'type' => 'numeric',
      'placeholder' => '',
      'description' => '',
    ],
    'POSTNET' => [
      'name' => 'POSTNET',
      'length' => 255,
      'type' => 'numeric',
      'placeholder' => '',
      'description' => '',
    ],
    'RMS4CC' => [
      'name' => 'RMS4CC (Royal Mail 4-state Customer Bar Code)',
      'length' => 255,
      'placeholder' => '',
      'description' => '',
    ],
    'S25+' => [
      'name' => 'Standard 2 of 5 + CHECKSUM',
      'length' => 255,
      'type' => 'numeric',
      'placeholder' => '',
      'description' => '',
    ],
    'S25' => [
      'name' => 'Standard 2 of 5',
      'length' => 255,
      'type' => 'numeric',
      'placeholder' => '',
      'description' => '',
    ],
    'UPCA' => [
      'name' => 'UPC-A',
      'length' => 12,
      'type' => 'numeric',
      'placeholder' => '',
      'description' => '',
    ],
    'UPCE' => [
      'name' => 'UPC-E',
      'length' => 12,
      'type' => 'numeric',
      'placeholder' => '',
      'description' => '',
    ],
    'LRAW' => [
      'name' => '1D RAW MODE (comma-separated rows of 01 strings)',
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
    foreach (['UPCA','UPCE', 'EAN13', 'EAN8', 'EAN5', 'EAN2'] as $code) {
      $barcodes[$code] = static::$types[$code]['name'];
    }
    return $barcodes;
  }

}
