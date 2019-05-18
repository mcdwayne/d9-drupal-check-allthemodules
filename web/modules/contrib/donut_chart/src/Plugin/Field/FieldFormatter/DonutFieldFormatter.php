<?php

namespace Drupal\donut_chart\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'donut_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "donut_field_formatter",
 *   label = @Translation("Donut chart"),
 *   field_types = {
 *     "list_float",
 *     "list_integer",
 *     "decimal",
 *     "float",
 *     "integer"
 *   }
 * )
 */
class DonutFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'relative_value' => '100',
      'main_color' => '#172b53',
      'main_width' => '5',
      'secondary_color' => '#ef9320',
      'secondary_width' => '3',
      'text_color' => '#172b53',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['relative_value'] = [
      '#title' => $this->t('Relative value'),
      '#description' => t('Insert the max value to calculate the percentage. Defaults to 100.'),
      '#type' => 'number',
      '#min' => '0',
      '#default_value' => $this->getSetting('relative_value'),
    ];
    $form['main_color'] = [
      '#title' => $this->t('Main ring color'),
      '#type' => 'textfield',
      '#size' => '9',
      '#attributes' => [
        'placeholder' => '#HEX',
      ],
      '#default_value' => $this->getSetting('main_color'),
    ];
    $form['main_width'] = [
      '#title' => $this->t('Main ring width'),
      '#type' => 'number',
      '#min' => '0',
      '#max' => '10',
      '#size' => '2',
      '#default_value' => $this->getSetting('main_width'),
    ];
    $form['secondary_color'] = [
      '#title' => $this->t('Secondary ring color'),
      '#type' => 'textfield',
      '#size' => '9',
      '#attributes' => [
        'placeholder' => '#HEX',
      ],
      '#default_value' => $this->getSetting('secondary_color'),
    ];
    $form['secondary_width'] = [
      '#title' => $this->t('Secondary ring width'),
      '#type' => 'number',
      '#min' => '0',
      '#max' => '10',
      '#size' => '2',
      '#default_value' => $this->getSetting('secondary_width'),
    ];
    $form['text_color'] = [
      '#title' => $this->t('Text color'),
      '#type' => 'textfield',
      '#size' => '9',
      '#attributes' => [
        'placeholder' => '#HEX',
      ],
      '#default_value' => $this->getSetting('text_color'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Displays the value as donut chart');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $value = $item->value / $this->getSetting('relative_value') * 100;
      $elements[$delta] = [
        '#theme' => 'donut_chart',
        '#value' => [
          'number' => $value,
          'main_color' => $this->getSetting('main_color'),
          'main_width' => $this->getSetting('main_width'),
          'secondary_color' => $this->getSetting('secondary_color'),
          'secondary_width' => $this->getSetting('secondary_width'),
          'text_color' => $this->getSetting('text_color'),
        ],
      ];
      $elements[$delta]['#attached']['library'][] = 'donut_chart/donut-chart';
    }

    return $elements;
  }

}
