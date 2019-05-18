<?php

namespace Drupal\phone_number\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use libphonenumber\PhoneNumberFormat;

/**
 * Plugin implementation of the 'phone_number_international' formatter.
 *
 * @FieldFormatter(
 *   id = "phone_number_international",
 *   label = @Translation("International Number"),
 *   field_types = {
 *     "phone_number",
 *     "telephone"
 *   }
 * )
 */
class PhoneNumberInternationalFormatter extends FormatterBase {

  public $phoneDisplayFormat = PhoneNumberFormat::INTERNATIONAL;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + ['as_link' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings() + static::defaultSettings();

    $element['as_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show as TEL link'),
      '#default_value' => $settings['as_link'],
    ];

    return parent::settingsForm($form, $form_state) + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings() + static::defaultSettings();

    if (!empty($settings['as_link'])) {
      $summary[] = $this->t('Show as TEL link');
    }
    else {
      $summary[] = $this->t('Show as plaintext');
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
      if ($phone_number = $util->getPhoneNumber($item->getValue()['value'], NULL, $item->getValue()['extension'])) {
        if (!empty($settings['as_link'])) {
          $element[$delta] = [
            '#type' => 'link',
            '#title' => $util->libUtil()->format($phone_number, $this->phoneDisplayFormat),
            '#url' => Url::fromUri('tel:' . $util->getCallableNumber($phone_number)),
          ];
        }
        else {
          $element[$delta] = [
            '#plain_text' => $util->libUtil()->format($phone_number, $this->phoneDisplayFormat),
          ];
        }
      }
    }

    return $element;
  }

}
