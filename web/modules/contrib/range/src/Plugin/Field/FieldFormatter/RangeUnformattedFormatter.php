<?php

namespace Drupal\range\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'range_unformatted' formatter.
 *
 * @FieldFormatter(
 *   id = "range_unformatted",
 *   label = @Translation("Unformatted"),
 *   field_types = {
 *     "range_integer",
 *     "range_decimal",
 *     "range_float"
 *   },
 *   weight = 10
 * )
 */
class RangeUnformattedFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'range_separator' => '-',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['range_separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Range separator.'),
      '#default_value' => $this->getSetting('range_separator'),
      '#weight' => 0,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $from_value = 1234.1234567890;
    $to_value = 4321.0987654321;

    $summary[] = $from_value . $this->getSetting('range_separator') . $to_value;

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $output = $item->from . $this->getSetting('range_separator') . $item->to;

      $elements[$delta] = ['#markup' => $output];
    }

    return $elements;
  }

}
