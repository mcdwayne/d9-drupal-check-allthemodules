<?php

namespace Drupal\hexidecimal_color\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Raw hexidecimal formatter for (Hexidecimal) Color fields.
 *
 * @FieldFormatter(
 *   id = "hexidecimal_color_raw_hex_display",
 *   label = @Translation("Raw Hexidecimal"),
 *   field_types = {
 *      "hexidecimal_color"
 *   }
 * )
 */
class HexColorRawHexDisplayFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'show_hash' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = [];

    $summary['overview'] = $this->t('Displays a hexidecimal representation of the color, with no HTML wrappers');
    if ($this->getSetting('show_hash')) {
      $show_hash = $this->t('Yes');
    }
    else {
      $show_hash = $this->t('No');
    }
    $summary['show_hash'] = $this->t('Prefix with hash (#) symbol: @value', ['@value' => $show_hash]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['show_hash'] = [
      '#type' => 'checkbox',
      '#title' => t('Prefix color with hash (#) symbol'),
      '#default_value' => $this->getSetting('show_hash'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      $color = $item->get('color')->getValue();
      $element[$delta] = [
        '#markup' => $this->getSetting('show_hash') ? $color : substr($color, 1),
      ];
    }

    return $element;
  }

}
