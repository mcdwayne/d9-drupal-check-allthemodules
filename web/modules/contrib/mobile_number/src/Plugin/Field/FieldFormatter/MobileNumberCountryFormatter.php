<?php

namespace Drupal\mobile_number\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'mobile_number_country' formatter.
 *
 * @FieldFormatter(
 *   id = "mobile_number_country",
 *   label = @Translation("Country"),
 *   field_types = {
 *     "mobile_number"
 *   }
 * )
 */
class MobileNumberCountryFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + ['type' => 'name'];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings() + static::defaultSettings();

    $form['type'] = [
      '#type' => 'radios',
      '#options' => [
        'name' => t('Country name'),
        'code' => t('Country code'),
      ],
      '#default_value' => $settings['type'],
    ];

    return parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings() + static::defaultSettings();

    if (!empty($settings['type'])) {
      $texts = [
        'name' => t('Show as country name'),
        'code' => t('Show as country code'),
      ];
      $summary[] = $texts[$settings['type']];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\mobile_number\MobileNumberUtilInterface $util */
    $util = \Drupal::service('mobile_number.util');
    $element = [];
    $settings = $this->getSettings() + static::defaultSettings();

    foreach ($items as $delta => $item) {
      /** @var \Drupal\mobile_number\Plugin\Field\FieldType\MobileNumberItem $item */
      if ($mobile_number = $util->getMobileNumber($item->getValue()['value'], NULL, [])) {
        if ($settings['type'] == 'code') {
          $element[$delta] = [
            '#plain_text' => $util->getCountry($mobile_number),
          ];
        }
        else {
          $element[$delta] = [
            '#plain_text' => $util->getCountryName($util->getCountry($mobile_number)),
          ];
        }
      }
    }

    return $element;
  }

}
