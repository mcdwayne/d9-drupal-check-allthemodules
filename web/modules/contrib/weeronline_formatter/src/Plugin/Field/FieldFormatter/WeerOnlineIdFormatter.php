<?php

namespace Drupal\weeronline_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'weeronline_id' formatter.
 *
 * @FieldFormatter(
 *   id = "weeronline_id",
 *   label = @Translation("WeerOnline Formatter"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class WeerOnlineIdFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      "temperature_scale" => "Celcius",
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['temperature_scale'] = [
      '#title' => $this->t('Set the temperature scale'),
      '#type' => 'select',
      '#description' => $this->t('Choose "Celcius" or "Fahrenheit"'),
      '#options' => [
        'Celcius' => $this->t('Celcius'),
        'Fahrenheit' => $this->t('Fahrenheit'),
      ],
      '#default_value' => (int) $this->getSetting('temperature_scale'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $temperature_scale = $this->getSetting('temperature_scale');
    if ($temperature_scale) {
      $summary[] = $this->t('Temepature Scale: @temperature_scale', ['@temperature_scale' => $this->getSetting('temperature_scale')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $settings = $this->getSettings();

    $temperature_scale = $settings['temperature_scale'];

    foreach ($items as $delta => $item) {
      $weeronline_id = $item->value;
      $elements[$delta] = [
        '#theme' => 'weeronline_id_formatter',
        '#weeronline_id' => $weeronline_id,
        '#temperature_scale' => $temperature_scale,
      ];
    }
    return $elements;
  }

}
