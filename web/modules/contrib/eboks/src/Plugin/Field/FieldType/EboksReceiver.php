<?php
/**
 * @file
 * Contains eboks_receiver field type definition.
 */

namespace Drupal\eboks\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\eboks\EboksSender;

/**
 * Defines the 'eboks_receiver' entity field type.
 *
 * @FieldType(
 *   id = "eboks_receiver",
 *   label = @Translation("Eboks receiver Id"),
 *   description = @Translation("A field containing a eBoks receiver Id value."),
 *   category = @Translation("E-Boks"),
 *   default_widget = "string_textfield",
 *   default_formatter = "string"
 * )
 */
class EboksReceiver extends StringItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
        'type' => 'CPR',
        'max_length' => 10,
        'is_ascii' => FALSE,
      ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['value'] = '12345678';
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = parent::storageSettingsForm($form, $form_state, $has_data);

    $element['type'] = [
      '#type' => 'select',
      '#options' => unserialize(EBOKS_RECEIVER_TYPES),
      '#title' => $this->t('E-Boks receiver type'),
      '#default_value' => $this->getSetting('type'),
      '#disabled' => $has_data,
      '#required' => TRUE,
    ];

    return $element;
  }

}
