<?php

namespace Drupal\multi_text\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'multi_text_string_widget' widget.
 *
 * @FieldWidget(
 *   id = "multi_text_string_widget",
 *   label = @Translation("Delimited Textfield"),
 *   field_types = {
 *     "string"
 *   },
 *   multiple_values = TRUE
 * )
 */
class MultiTextStringWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => 60,
      'placeholder' => '',
      'delimiter' => ', ',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['size'] = [
      '#type' => 'number',
      '#title' => t('Size of textfield'),
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
    $elements['delimiter'] = [
      '#type' => 'textfield',
      '#title' => t('Delimiter'),
      '#default_value' => $this->getSetting('delimiter'),
      '#description' => t('The string that will be used to separate individual field values.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Textfield size: @size', ['@size' => $this->getSetting('size')]);
    if (!empty($this->getSetting('placeholder'))) {
      $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $this->getSetting('placeholder')]);
    }
    if (!empty($this->getSetting('delimiter'))) {
      $summary[] = t('Delimiter: \'@delimiter\'', ['@delimiter' => $this->getSetting('delimiter')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $delimiter = $this->getSetting('delimiter');
    $default_value = [];
    foreach ($items as $delta => $item) {
      $default_value[] = $item->value;
    }

    $element['value'] = $element + [
      '#type' => 'textfield',
      '#default_value' => join($delimiter, $default_value),
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => $this->getFieldSetting('max_length'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $delimiter = $this->getSetting('delimiter');
    $values = explode($delimiter, reset($values));
    foreach ($values as $delta => $value) {
      $values[$delta] = trim($value);
    }
    return $values;
  }
}
