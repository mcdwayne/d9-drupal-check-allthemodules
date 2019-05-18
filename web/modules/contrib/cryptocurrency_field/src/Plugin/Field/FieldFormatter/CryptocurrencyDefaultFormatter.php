<?php

namespace Drupal\cryptocurrency_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'cryptocurrency_default_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "cryptocurrency_default_formatter",
 *   label = @Translation("Default Cryptocurrency Formatter"),
 *   field_types = {
 *     "cryptocurrency_field"
 *   }
 * )
 */
class CryptocurrencyDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();

    $summary[] = t('Displays the address.');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      // Render each element as markup.
      $element[$delta] = [
        '#type' => 'markup',
        '#markup' => $item->value,
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'explorer_url' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['explorer_url'] = [
      '#title' => t('Explorer URL'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('explorer_url'),
    ];

    return $element;
  }

}
