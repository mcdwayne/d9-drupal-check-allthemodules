<?php

namespace Drupal\record\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Plugin implementation of the 'record_item_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "record_item_formatter",
 *   module = "record",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "record_item"
 *   }
 * )
 */
class RecordFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $definitions = $items->getFieldDefinition()->getFieldStorageDefinition()->getPropertyDefinitions();
    $show_labels = $this->getSetting('labels');

    foreach ($items as $delta => $item) {
      $prepared_values = [];
      foreach ($item->getValue() as $key => $value) {
        if ($key != 'archived_fields' && isset($definitions[$key])) {
          $prepared_values[$key] = [
            'value' => $value,
            'label' => $show_labels ? $definitions[$key]->getLabel() : '',
            'key' => $key,
          ];
        }
      }
      $elements[$delta] = [
        '#theme' => 'record_properties',
        '#properties' => $prepared_values,
        '#cache' => [
          'tags' => [],
        ],
      ];
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'labels' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['labels'] = [
      '#type' => 'checkbox',
      '#title' => t('Show labels'),
      '#default_value' => $this->getSetting('labels'),
    ];

    return $elements;
  }

}
