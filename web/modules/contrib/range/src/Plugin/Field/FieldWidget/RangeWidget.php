<?php

namespace Drupal\range\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'range' widget.
 *
 * @FieldWidget(
 *   id = "range",
 *   label = @Translation("Text fields"),
 *   field_types = {
 *     "range_integer",
 *     "range_float",
 *     "range_decimal"
 *   }
 * )
 */
class RangeWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'placeholder' => [
        'from' => '',
        'to' => '',
      ],
      'label' => [
        'from' => t('From'),
        'to' => t('to'),
      ],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $element['label']['from'] = [
      '#type' => 'textfield',
      '#title' => $this->t('FROM form element label'),
      '#default_value' => $this->getSetting('label')['from'],
      '#description' => $this->t('Define label for the form element.'),
      '#required' => TRUE,
    ];
    $element['label']['to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('TO form element label'),
      '#default_value' => $this->getSetting('label')['to'],
      '#description' => $this->t('Define label for the form element.'),
      '#required' => TRUE,
    ];
    $element['placeholder']['from'] = [
      '#type' => 'textfield',
      '#title' => $this->t('FROM placeholder'),
      '#default_value' => $this->getSetting('placeholder')['from'],
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];
    $element['placeholder']['to'] = [
      '#type' => 'textfield',
      '#title' => $this->t('TO placeholder'),
      '#default_value' => $this->getSetting('placeholder')['to'],
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('FROM form element label: @label', ['@label' => $this->getSetting('label')['from']]);
    $summary[] = $this->t('TO form element label: @label', ['@label' => $this->getSetting('label')['to']]);

    $placeholder = $this->getSetting('placeholder');
    $summary[] = !empty($placeholder['from']) ? $this->t('FROM placeholder: @placeholder', ['@placeholder' => $placeholder['from']]) : $this->t('No FROM placeholder');
    $summary[] = !empty($placeholder['to']) ? $this->t('TO placeholder: @placeholder', ['@placeholder' => $placeholder['to']]) : $this->t('No TO placeholder');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $from = isset($items[$delta]->from) ? $items[$delta]->from : NULL;
    $to = isset($items[$delta]->to) ? $items[$delta]->to : NULL;

    // Wrap in a fieldset for single field.
    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() === 1) {
      $element['#type'] = 'fieldset';
    }

    $base = [
      '#type' => 'number',
      '#required' => $element['#required'],
    ];

    // Set the step for floating point and decimal numbers.
    switch ($this->fieldDefinition->getType()) {
      case 'range_decimal':
        $base['#step'] = pow(0.1, $this->getFieldSetting('scale'));
        break;

      case 'range_float':
        $base['#step'] = 'any';
        break;
    }

    // Set minimum and maximum.
    if (is_numeric($this->getFieldSetting('min'))) {
      $base['#min'] = $this->getFieldSetting('min');
    }
    if (is_numeric($this->getFieldSetting('max'))) {
      $base['#max'] = $this->getFieldSetting('max');
    }

    $element['from'] = [
      '#title' => $this->getSetting('label')['from'],
      '#default_value' => $from,
      '#placeholder' => $this->getSetting('placeholder')['from'],
      '#prefix' => '<div class="field--widget-range-text-fields clearfix">',
    ] + $base;

    $element['to'] = [
      '#title' => $this->getSetting('label')['to'],
      '#default_value' => $to,
      '#placeholder' => $this->getSetting('placeholder')['to'],
      '#suffix' => '</div>',
    ] + $base;

    // Add prefixes and suffixes.
    $this->formElementSubElementPrefixSuffix($element, 'from');
    $this->formElementSubElementPrefixSuffix($element, 'to');

    $element['#attached']['library'][] = 'range/range.field-widget';

    return $element;
  }

  /**
   * Helper method. Adds prefix/suffix to a range field widget subelements.
   *
   * @param array $element
   *   Range field widget definition array.
   * @param string $element_name
   *   Form element machine name.
   */
  protected function formElementSubElementPrefixSuffix(array &$element, $element_name) {
    $setting = $this->getFieldSetting($element_name);
    if (!empty($setting['prefix'])) {
      $element[$element_name]['#field_prefix'] = FieldFilteredMarkup::create($setting['prefix']);
    }
    if (!empty($setting['suffix'])) {
      $element[$element_name]['#field_suffix'] = FieldFilteredMarkup::create($setting['suffix']);
    }
  }

}
