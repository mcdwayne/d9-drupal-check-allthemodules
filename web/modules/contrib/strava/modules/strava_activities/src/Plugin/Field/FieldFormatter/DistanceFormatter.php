<?php

namespace Drupal\strava_activities\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\DecimalFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin for numeric distance formatters.
 *
 * @FieldFormatter(
 *   id = "number_distance",
 *   label = @Translation("Distance"),
 *   field_types = {
 *     "decimal",
 *     "float"
 *   }
 * )
 */
class DistanceFormatter extends DecimalFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'distance_unit' => 'km',
        'suffix_unit' => TRUE,
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $options = [
      'm' => t('Meters'),
      'km' => t('Kilometers'),
      'ft' => t('Feet'),
      'mi' => t('Miles'),
    ];
    $elements['distance_unit'] = [
      '#type' => 'select',
      '#title' => t('Distance unit'),
      '#options' => $options,
      '#default_value' => $this->getSetting('distance_unit'),
      '#weight' => 0,
    ];

    $elements['suffix_unit'] = [
      '#type' => 'checkbox',
      '#title' => t('Display unit as suffix'),
      '#default_value' => $this->getSetting('suffix_unit'),
      '#weight' => 10,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->numberFormat(1234.1234567890);
    if ($this->getSetting('suffix_unit')) {
      $summary[] = t('Display with <em>@unit</em> as suffix.', ['@unit' => $this->getSetting('distance_unit')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $value = $this->convertUnit($item->value);
      $output = $this->numberFormat($value);

      // Account for prefix and suffix.
      if ($this->getSetting('suffix_unit')) {
        $suffix = $this->getSetting('distance_unit');
        $output = $output . $suffix;
      }
      // Output the raw value in a content attribute if the text of the HTML
      // element differs from the raw value (for example when a prefix is used).
      if (isset($item->_attributes) && $item->value != $output) {
        $item->_attributes += ['content' => $item->value];
      }

      $elements[$delta] = ['#markup' => $output];
    }

    return $elements;
  }

  /**
   * Change the distance in meters to the configured measurement unit.
   *
   * @param $value
   *
   * @return float|int
   */
  private function convertUnit($value) {
    $unit = $this->getSetting('distance_unit');
    switch ($unit) {
      case 'm':
      default:
        return $value;
        break;

      case 'km':
        return $value / 1000;

      case 'ft':
        return $value * 3.2808;

      case 'mi':
        return $value * 0.00062137;
    }
  }

}
