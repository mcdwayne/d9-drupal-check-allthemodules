<?php

namespace Drupal\barcode\Plugin\Field\FieldFormatter;

use Com\Tecnick\Barcode\Barcode;
use Drupal\barcode\Plugin\Field\FieldType\BarcodeBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base for barcode formatters.
 */
abstract class BarcodeFormatterBase extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'background' => 'FFFFFF',
      'foreground' => '000000',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();

    $elements['background'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background Color'),
      '#default_value' => $settings['background'],
      '#maxlength' => 6,
    ];

    $elements['foreground'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Foreground Color'),
      '#default_value' => $settings['foreground'],
      '#maxlength' => 6,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();

    if ($settings['background']) {
      $summary[] = $this->t('Custom background color: @background', [
        '@background' => $settings['background'],
      ]);
    }
    if ($settings['foreground']) {
      $summary[] = $this->t('Custom Foreground color: @foreground', [
        '@foreground' => $settings['foreground'],
      ]);
    }

    return $summary;
  }

  /**
   * Generate the barcode object.
   *
   * @param Drupal\barcode\Plugin\Field\FieldType\BarcodeBase $field
   *   The field.
   * @param str $background
   *   The background color.
   * @param str $foreground
   *   The foreground color.
   *
   * @return Com\Tecnick\Barcode\Type
   *   The barcode object.
   */
  protected function generateBarcode(BarcodeBase $field, $background, $foreground) {
    $barcode_generator = new Barcode();
    return $barcode_generator->getBarcodeObj(
      $field->type,                     // barcode type and additional comma-separated parameters
      $field->value,                   // data string to encode
      -2,                             // bar height (use absolute or negative value as multiplication factor)
      -50,                             // bar width (use absolute or negative value as multiplication factor)
      $foreground,                        // foreground color
      [10, 10, 10, 10]           // padding (use absolute or negative values as multiplication factors)
      )->setBackgroundColor($background); // background color
  }


}
