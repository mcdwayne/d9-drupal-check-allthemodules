<?php

namespace Drupal\external_data_source\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'external_data_source_widget' widget.
 *
 * @FieldWidget(
 *   id = "external_data_source_auto_complete_widget",
 *   label = @Translation("External data source auto complete widget"),
 *   field_types = {
 *     "external_data_source"
 *   },
 *   multiple_values = TRUE
 * )
 */
class ExternalDataSourceAutoCompleteWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'size' => 60,
        'placeholder' => '',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];
    $elements['size'] = [
      '#type' => 'number',
      '#title' => t('Size of text field'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    ];
    $elements['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    //retrieve settings from field Storage
    $fieldSettings = $this->getFieldSettings();
    $summary[] = t('Text field size: @size', ['@size' => $this->getSetting('size')]);
    if (!empty($this->getSetting('placeholder'))) {
      $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $this->getSetting('placeholder')]);
    }
    if (!empty($fieldSettings['ws'])) {
      $summary[] = t('Web Service Plugin: @ws', ['@ws' => $fieldSettings['ws']]);
    }
    if (!empty($fieldSettings['count'])) {
      $summary[] = t('Number of suggestions: @count', ['@count' => $fieldSettings['count']]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    //retrieve settings from field Storage
    $fieldSettings = $this->getFieldSettings();
    $element['value'] = $element + [
        '#type' => 'textfield',
        '#autocomplete_route_name' => 'external_data_source.auto_complete_controller',
        '#autocomplete_route_parameters' => [
          'plugin_name' => $fieldSettings['ws'],
          'count' => $fieldSettings['count'],
        ],
        '#attributes' => ['class' => ['input-lg', 'form-control']], //Bootstrap 4 ready
        '#maxlength' => 1024,
        '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : '',
        '#size' => $this->getSetting('size'),
        '#placeholder' => $this->getSetting('placeholder'),
      ];
    return $element;
  }

}
