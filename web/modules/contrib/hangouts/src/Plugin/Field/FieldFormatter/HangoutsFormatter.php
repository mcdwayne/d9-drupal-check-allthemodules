<?php

namespace Drupal\hangouts\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hangouts\HangoutsUtils;

/**
 * Plugin implementation of hangouts formatter.
 *
 * @FieldFormatter(
 *   id = "hangouts_button",
 *   label = @Translation("Hangouts button"),
 *   field_types = {
 *     "string",
 *   }
 * )
 */
class HangoutsFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $settings = $this->getSettings();
    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#theme' => 'hangouts',
        '#hangouts_gid' => $item->value,
        '#hangouts_size' => $settings['hangouts_size'],
      ];
    }
    return $element;
  }

  /**
   * Settings form for formatter.
   *
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['hangouts_size'] = [
      '#type' => 'radios',
      '#title' => t('Button size'),
      '#options' => HangoutsUtils::getHangoutsImages(),
      '#default_value' => $this->getSetting('hangouts_size'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $summary = [];
    $summary[] = t('Button size: @size', ['@size' => $settings['hangouts_size']]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'hangouts_size' => '79x15',
    ] + parent::defaultSettings();
  }

}
