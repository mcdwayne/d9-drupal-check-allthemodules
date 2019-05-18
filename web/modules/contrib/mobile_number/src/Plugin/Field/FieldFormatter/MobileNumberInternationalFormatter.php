<?php

namespace Drupal\mobile_number\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'mobile_number_international' formatter.
 *
 * @FieldFormatter(
 *   id = "mobile_number_international",
 *   label = @Translation("International Number"),
 *   field_types = {
 *     "mobile_number",
 *     "telephone"
 *   }
 * )
 */
class MobileNumberInternationalFormatter extends FormatterBase {

  public $phoneDisplayFormat = 1;

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
      '#title' => t('Show as TEL link'),
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
      $summary[] = t('Show as TEL link');
    }
    else {
      $summary[] = t('Show as plaintext');
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
        if (!empty($settings['as_link'])) {
          $element[$delta] = [
            '#type' => 'link',
            '#title' => $util->libUtil()->format($mobile_number, $this->phoneDisplayFormat),
            '#url' => Url::fromUri("tel:" . $util->getCallableNumber($mobile_number)),
          ];
        }
        else {
          $element[$delta] = [
            '#plain_text' => $util->libUtil()->format($mobile_number, $this->phoneDisplayFormat),
          ];
        }
      }
    }

    return $element;
  }

}
