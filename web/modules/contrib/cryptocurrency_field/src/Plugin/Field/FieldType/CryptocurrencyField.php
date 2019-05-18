<?php

namespace Drupal\cryptocurrency_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a cryptocurrency address field.
 * 
 * @FieldType(
 *   id = "cryptocurrency_field",
 *   label = @Translation("Cryptocurrency Field"),
 *   default_formatter = "cryptocurrency_default_formatter",
 *   default_widget = "cryptocurrency_default_widget",
 * )
 */

class CryptocurrencyField extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      // columns contains the values that the field will store
      'columns' => array(
        // List the values that the field will save. This
        // field will only save a single value, 'value'
        'value' => array(
          'type' => 'text',
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['value'] = DataDefinition::create('string');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'currency_type' => 'bitcoin_cash',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
  
    $element = [];
    // The key of the element should be the setting name
    $element['currency_type'] = [
      '#title' => $this->t('Currency'),
      '#type' => 'select',
      '#options' => [
        'bitcoin_cash' => $this->t('BCH'),
//        'bitcoin_legacy' => $this->t('BTC'),
      ],
      '#default_value' => $this->getSetting('currency_type'),
    ];

    return $element;
  }

}
