<?php

namespace Drupal\boolean_single_state_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\BooleanFormatter;

/**
 * Plugin implementation of the 'boolean' formatter.
 *
 * @FieldFormatter(
 *   id = "boolean_single_state_formatter",
 *   label = @Translation("Boolean single state"),
 *   field_types = {
 *     "boolean",
 *   }
 * )
 */
class BooleanSingleStateFormatter extends BooleanFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['format_inverse_state'] = 0;
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['format_inverse_state'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable display for the "off" state instead of the "on" one'),
      '#default_value' => $this->getSetting('format_inverse_state'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $setting = $this->getSetting('format_inverse_state');
    $summary = parent::settingsSummary();
    $summary[] = $setting ? $this->t('Display only for "off" state') : $this->t('Display only for "on" state');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $setting = $this->getSetting('format_inverse_state');

    foreach ($items as $delta => $item) {
      if (!($setting xor $item->value)) {
        unset($elements[$delta]);
      }
    }

    return $elements;
  }

}
