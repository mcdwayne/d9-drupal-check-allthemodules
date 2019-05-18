<?php

namespace Drupal\exif\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\exif\ExifFactory;

/**
 * Class ExifFieldWidgetBase provide base methods for all widgets.
 *
 * @package Drupal\exif\Plugin\Field\FieldWidget
 */
abstract class ExifFieldWidgetBase extends ExifWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return
      [
        'exif_field_separator' => '',
        'exif_field' => 'naming_convention',
      ]
      + parent::defaultSettings();
  }

  /**
   * Ensure field is correctly configured.
   *
   * @param array $element
   *   A form element array containing basic properties for the widget.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $form
   *   The form structure where widgets are being attached to.
   */
  public static function validateExifField(array $element, FormStateInterface $form_state, array $form) {
    $elementSettings = $form_state->getValue($element['#parents']);
    if (!$elementSettings) {
      $message = t('you must choose at least one method to retrieve image metadata.');
      $form_state->setErrorByName('exif_field', $message);
    }
  }

  /**
   * Ensure field separator is correctly configured.
   *
   * @param array $element
   *   A form element array containing basic properties for the widget.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateExifFieldSeparator(array $element, FormStateInterface &$form_state) {
    $elementSettings = $form_state->getValue($element['#parents']);
    if (!empty($elementSettings) && strlen($elementSettings) > 1) {
      $message = t('the separator is only one character long.');
      $form_state->setErrorByName('exif_field_separator', $message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $exif_fields = $this->retrieveExifFields();
    $default_exif_value = $this->retrieveExifFieldDefaultValue();
    $default_exif_separator_value = $this->retrieveExifFieldDefaultSeparatorValue();
    $element['exif_field'] = [
      '#type' => 'select',
      '#title' => t('exif field data'),
      '#description' => t('choose to retrieve data from the image field referenced with the selected name or by naming convention.'),
      '#options' => array_merge(['naming_convention' => 'name of the field is used as the exif field name'], $exif_fields),
      '#default_value' => $default_exif_value,
      '#element_validate' => [
        [
          get_class($this),
          'validateExifField',
        ],
      ],
    ];
    $element['exif_field_separator'] = [
      '#type' => 'textfield',
      '#title' => t('exif field separator'),
      '#description' => t('separator used to split values (if field definition support several values). let it empty if you do not want to split values.'),
      '#default_value' => $default_exif_separator_value,
      '#element_validate' => [
        [
          get_class($this),
          'validateExifFieldSeparator',
        ],
      ],
    ];
    return $element;
  }

  /**
   * List of possible fields.
   *
   * @return array
   *   List of possible exif fields.
   */
  private function retrieveExifFields() {
    $exif = ExifFactory::getExifInterface();
    return $exif->getFieldKeys();
  }

  /**
   * Get exif field name associated to this drupal field.
   *
   * If none found, use naming convention.
   *
   * @return string
   *   name of the exif field or string 'naming_convention'.
   */
  private function retrieveExifFieldDefaultValue() {
    $result = $this->getSetting('exif_field');
    if (empty($result)) {
      $result = 'naming_convention';
    }
    return $result;
  }

  /**
   * Get separator value from settings.
   *
   * @return string
   *   separator value or empty string.
   */
  private function retrieveExifFieldDefaultSeparatorValue() {
    $result = $this->getSetting('exif_field_separator');
    if (empty($result)) {
      $result = '';
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $exif_field_separator = $this->getSetting('exif_field_separator');
    if (isset($exif_field_separator) && strlen($exif_field_separator) == 1) {
      $exif_field_msg = t("exif value will be split using character separator '@separator'", ['@separator' => $exif_field_separator]);
    }
    else {
      $exif_field_msg = t('exif value will be extracted as one value');
    }
    array_unshift($summary, $exif_field_msg);

    $exif_field = $this->getSetting('exif_field');
    if (isset($exif_field) && $exif_field != 'naming_convention') {
      $exif_field_msg = t("exif data will be extracted from image metadata field '@metadata'", ['@metadata' => $exif_field]);
    }
    else {
      $fieldname = $this->fieldDefinition->getName();
      $exif_field = str_replace("field_", "", $fieldname);
      $exif_field_msg = t("Using naming convention. so the exif data will be extracted from image metadata field '@metadata'", ['@metadata' => $exif_field]);
    }
    array_unshift($summary, $exif_field_msg);

    return $summary;
  }

}
