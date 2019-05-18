<?php

namespace Drupal\dimension\Plugin\Field\FieldWidget;

use Drupal\dimension\Plugin\Field\Basic;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\NumberWidget;
use Drupal\Core\Form\FormStateInterface;

abstract class Dimension extends NumberWidget implements Basic {

  /**
   * @inheritdoc
   */
  protected static function _defaultSettings($fields) {
    $settings = array();
    foreach ($fields as $key => $label) {
      $settings[$key] = array(
        'placeholder' => '',
        'label' => $label,
        'description' => '',
      );
    }
    return $settings;
  }

  /**
   * @inheritdoc
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = array();
    foreach ($this->fields() as $key => $label) {
      $settings = $this->getSetting($key);
      $element[$key] = array(
        '#type' => 'fieldset',
        '#title' => $settings['label'],
      );
      $element[$key]['label'] = array(
        '#type' => 'textfield',
        '#title' => t('Label'),
        '#default_value' => $settings['label'],
        '#required' => TRUE,
        '#description' => t(''),
      );
      $element[$key]['placeholder'] = array(
        '#type' => 'textfield',
        '#title' => t('Placeholder'),
        '#default_value' => $settings['placeholder'],
        '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
      );
      $element[$key]['description'] = array(
        '#type' => 'textfield',
        '#title' => t('Description'),
        '#default_value' => $settings['description'],
        '#description' => t(''),
      );
    }
    return $element;
  }

  /**
   * @inheritdoc
   */
  public function settingsSummary() {
    $summary = array();
    foreach ($this->fields() as $key => $label) {
      $settings = $this->getSetting($key);
      $placeholder = $settings['placeholder'];
      if (!empty($placeholder)) {
        $summary[] = t('@label: @placeholder', array('@label' => $settings['label'], '@placeholder' => $placeholder));
      }
      else {
        $summary[] = t('@label: No placeholder', array('@label' => $settings['label']));
      }
    }
    return $summary;
  }

  /**
   * @inheritdoc
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $default = $items[$delta]->getFieldDefinition()->getDefaultValue($items[$delta]->getEntity());
    $element += array(
      '#type' => 'fieldset',
    );

    $arguments = array();

    foreach ($this->fields() as $key => $label) {
      $settings = $this->getSetting($key);
      $field_settings = $this->getFieldSetting($key);
      $field_storage_settings = $this->getFieldSetting('storage_' . $key);
      $value = isset($items[$delta]->{$key}) ? $items[$delta]->{$key} : (isset($default[0][$key]) ? $default[0][$key] : NULL);

      $arguments['fields'][$key] = array(
        'scale' => $field_storage_settings['scale'],
        'factor' => $field_settings['factor'],
      );

      $element[$key] = array(
        '#type' => 'number',
        '#title' => $settings['label'],
        '#default_value' => $value,
        '#placeholder' => $settings['placeholder'],
        '#step' => pow(0.1, $field_storage_settings['scale']),
        '#description' => $settings['description'],
        '#attributes' => array(
          'dimension-key' => $key,
        )
      );

      // Set minimum and maximum.
      if (is_numeric($field_settings['min'])) {
        $element[$key]['#min'] = $field_settings['min'];
      }
      if (is_numeric($field_settings['max'])) {
        $element[$key]['#max'] = $field_settings['max'];
      }

      // Add prefix and suffix.
      if ($field_settings['prefix']) {
        $prefixes = explode('|', $field_settings['prefix']);
        $element[$key]['#field_prefix'] = FieldFilteredMarkup::create(array_pop($prefixes));
      }
      if ($field_settings['suffix']) {
        $suffixes = explode('|', $field_settings['suffix']);
        $element[$key]['#field_suffix'] = FieldFilteredMarkup::create(array_pop($suffixes));
      }
    }

    $element['value'] = array(
      '#type' => 'number',
      '#title' => t('Dimension'),
      '#default_value' => '',
      '#disabled' => TRUE,
      '#attributes' => array(
        'dimension-key' => 'value',
      )
    );
    $field_settings = $this->getFieldSetting('value');
    $field_storage_settings = $this->getFieldSetting('storage_value');
    $arguments['value'] = array(
      'scale' => $field_storage_settings['scale'],
      'factor' => $field_settings['factor'],
    );
    // Add prefix and suffix.
    if ($field_settings['prefix']) {
      $prefixes = explode('|', $field_settings['prefix']);
      $element['value']['#field_prefix'] = FieldFilteredMarkup::create(array_pop($prefixes));
    }
    if ($field_settings['suffix']) {
      $suffixes = explode('|', $field_settings['suffix']);
      $element['value']['#field_suffix'] = FieldFilteredMarkup::create(array_pop($suffixes));
    }

    $id = $this->fieldDefinition->getConfig($items[$delta]->getEntity()->bundle())->id();
    $element['#attached']['library'][] = 'dimension/widget';
    $element['#attached']['drupalSettings']['dimension'][$id] = $arguments;
    $element['#attributes']['dimension-id'] = $id;
    $element['#attributes']['class'][] = 'dimension-wrapper';

    return $element;
  }

}
