<?php

/**
 * @file
 * Contains \Drupal\field_image_style\Plugin\Field\FieldType\ImageStyleItem.
 */

namespace Drupal\field_image_style\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\options\Plugin\Field\FieldType\ListItemBase;

/**
 * Plugin implementation of the 'image_style' field type.
 *
 * @FieldType(
 *   id = "image_style",
 *   label = @Translation("Image Style"),
 *   description = @Translation("This field stores an image style in the database"),
 *   default_widget = "options_select"
 * )
 */
class ImageStyleItem extends ListItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return array('allowed_values' => array());
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Text value'))
      ->addConstraint('Length', array('max' => 255))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
          'length' => 255,
        ),
      ),
      'indexes' => array(
        'value' => array('value'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    $allowed_options = image_style_options(FALSE);
    $allowed_values = $this->getFieldDefinition()->getFieldStorageDefinition()->getSetting('allowed_values');
    if(!empty($allowed_values)) {
      $allowed_options = array_intersect_key($allowed_options, array_filter($allowed_values));
    }
    return $allowed_options;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $allowed_options = $field_definition->getFieldStorageDefinition()->getSetting('allowed_values');
    $values['value'] = array_rand($allowed_options);
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {

    $element['allowed_values'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Allowed image styles'),
      '#default_value' => $this->getSetting('allowed_values'),
      '#element_validate' => array(array(get_class($this), 'validateAllowedValues')),
      '#field_has_data' => $has_data,
      '#field_name' => $this->getFieldDefinition()->getName(),
      '#entity_type' => $this->getEntity()->getEntityTypeId(),
      '#options' => image_style_options(FALSE),
      '#allowed_values' => $this->getSetting('allowed_values'),
      '#description' => $this->allowedValuesDescription(),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function allowedValuesDescription() {
    $description = '<p>' . t('The possible values this field can contain. Leave empty if you want to allow all values') . '</p>';
    return $description;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateAllowedValues($element, FormStateInterface $form_state) {
    $values = $element['#value'];

    // Check that keys are valid for the field type.
    foreach ($values as $key => $value) {
      if ($error = static::validateAllowedValue($key)) {
        $form_state->setError($element, $error);
        break;
      }
    }

    // Prevent removing values currently in use.
    if ($element['#field_has_data']) {
      $lost_keys = array_keys(array_diff_key($element['#allowed_values'], $values));
      if (_options_values_in_use($element['#entity_type'], $element['#field_name'], $lost_keys)) {
        $form_state->setError($element, t('Allowed values list: some values are being removed while currently in use.'));
      }
    }

    $form_state->setValueForElement($element, $values);
  }

  /**
   * {@inheritdoc}
   */
  protected static function validateAllowedValue($option) {
    $image_style_options = image_style_options(FALSE);
    if(!array_key_exists($option, $image_style_options)) {
      return t('Image style @name does\'nt exist.', array('@name' => $option));
    }
  }


}
