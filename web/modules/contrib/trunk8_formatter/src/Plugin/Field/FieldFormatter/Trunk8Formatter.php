<?php

namespace Drupal\trunk8_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Trunk8' formatter.
 *
 * @FieldFormatter(
 *   id = "Trunk8",
 *   label = @Translation("Trunk8"),
 *   field_types= {
 *     "string",
 *     "string_long",
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *   }
 * )
 */
class Trunk8Formatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();

    $settings['numlines'] = 1;
    $settings['fill'] = '...';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['numlines'] = [
      '#type' => 'textfield',
      '#size' => 3,
      '#title' => t('Number of lines'),
      '#element_validate' => ['element_validate_integer_positive'],
      '#description' => t('Number of lines to show'),
      '#default_value' => $this->getSetting('numlines'),
    ];
    $element['fill'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#title' => t('Fill'),
      '#description' => t('Defaults to ellipses'),
      '#default_value' => $this->getSetting('fill'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();

    $summary[] = t('Trim to %numlines lines using %fill', [
      '%numlines' => $settings['numlines'],
      '%fill' => $settings['fill'],
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#type' => 'trunk8_processed_text',
        '#fill' => $this->getSetting('fill'),
        '#numlines' => $this->getSetting('numlines'),
        '#format' => $item->format,
        '#langcode' => $item->getLangcode(),
        '#text' => $item->value,
        '#attached' => [
          'library' => [
            'trunk8_formatter/trunk8_formatter.trunk8js',
          ],
        ],
      ];
    }

    return $element;
  }

}
