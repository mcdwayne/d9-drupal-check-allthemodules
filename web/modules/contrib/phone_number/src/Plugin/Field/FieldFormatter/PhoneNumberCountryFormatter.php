<?php

namespace Drupal\phone_number\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'phone_number_country' formatter.
 *
 * @FieldFormatter(
 *   id = "phone_number_country",
 *   label = @Translation("Country"),
 *   field_types = {
 *     "phone_number"
 *   }
 * )
 */
class PhoneNumberCountryFormatter extends FormatterBase {

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
        'name' => $this->t('Country name'),
        'code' => $this->t('Country code'),
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
        'name' => $this->t('Show as country name'),
        'code' => $this->t('Show as country code'),
      ];
      $summary[] = $texts[$settings['type']];
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    /** @var \Drupal\phone_number\PhoneNumberUtilInterface $util */
    $util = \Drupal::service('phone_number.util');
    $element = [];
    $settings = $this->getSettings() + static::defaultSettings();

    foreach ($items as $delta => $item) {
      /** @var \Drupal\phone_number\Plugin\Field\FieldType\PhoneNumberItem $item */
      if ($phone_number = $util->getPhoneNumber($item->getValue()['value'])) {
        if ($settings['type'] == 'code') {
          $element[$delta] = [
            '#plain_text' => $util->getCountry($phone_number),
          ];
        }
        else {
          $element[$delta] = [
            '#plain_text' => $util->getCountryName($util->getCountry($phone_number)),
          ];
        }
      }
    }

    return $element;
  }

}
