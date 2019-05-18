<?php

namespace Drupal\colorapi\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Raw hexadecimal formatter for Color API Color fields.
 *
 * @FieldFormatter(
 *   id = "colorapi_raw_hex_display",
 *   label = @Translation("Raw Hexadecimal Color"),
 *   description = @Translation("Displays the color in hexadecimal color format, with no wrappers"),
 *   field_types = {
 *      "colorapi_color_field"
 *   }
 * )
 */
class ColorapiRawHexDisplayFormatter extends ColorapiDisplayFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'show_hash' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = [];

    $summary['overview'] = $this->t('Displays a hexadecimal representation of the color, with no HTML wrappers');
    if ($this->getSetting('show_hash')) {
      $show_hash = $this->t('Yes');
    }
    else {
      $show_hash = $this->t('No');
    }
    $summary['show_hash'] = $this->t('Prefix with hash (#) symbol: @value', ['@value' => $show_hash]);

    return $summary + parent::settingsSummary();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

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
      $this->addHumanReadableNameToElement($element, $delta, $item);

      $color = $item->getHexadecimal();
      $element[$delta]['color'] = [
        '#markup' => $this->getSetting('show_hash') ? $color : substr($color, 1),
      ];
    }

    return $element;
  }

}
