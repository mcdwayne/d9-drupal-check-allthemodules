<?php

namespace Drupal\radioactivity\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'radioactivity_value' formatter.
 *
 * @FieldFormatter(
 *   id = "radioactivity_value",
 *   label = @Translation("Value"),
 *   field_types = {
 *     "radioactivity"
 *   }
 * )
 */
class RadioactivityValue extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'decimals' => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      'decimals' => [
        '#title' => $this->t('Decimals'),
        '#type' => 'number',
        '#min' => 0,
        '#required' => TRUE,
        '#description' => $this->t('The number of decimals to show.'),
        '#default_value' => $this->getSetting('decimals'),
      ],
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = t('Decimals: @number', ['@number' => $this->getSetting('decimals')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if (!$item->isEmpty()) {
        $elements[$delta] = [
          '#markup' => $this->viewValue($item->energy),
        ];
      }
    }

    return $elements;
  }

  /**
   * Format the energy value according to the settings.
   *
   * @param float $energy
   *   Energy value.
   *
   * @return string
   *   Formatted number.
   */
  protected function viewValue($energy) {
    return number_format($energy, $this->getSetting('decimals'));
  }

}
