<?php

namespace Drupal\address_dawa\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\address_dawa\AddressDawaItemInterface;

/**
 * Plugin implementation of the 'address_dawa' field type.
 *
 * @FieldType(
 *   id = "address_dawa",
 *   label = @Translation("Address DAWA"),
 *   description = @Translation("An entity field containing a postal address"),
 *   default_widget = "address_dawa",
 *   default_formatter = "address_dawa",
 *   constraints = {"AddressDawa" = {}}
 * )
 */
class AddressDawaItem extends FieldItemBase implements AddressDawaItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'type' => [
          'type' => 'varchar',
          'description' => 'Address type',
          'length' => 255,
        ],
        'id' => [
          'type' => 'varchar',
          'description' => 'Address UUID',
          'length' => 255,
        ],
        'status' => [
          'type' => 'int',
          'description' => 'Address status',
        ],
        'value' => [
          'type' => 'varchar',
          'description' => 'Address textual value form user input',
          'length' => 255,
        ],
        'lat' => [
          'type' => 'float',
          'description' => 'Address latitude coordinate',
        ],
        'lng' => [
          'type' => 'float',
          'description' => 'Address longitude coordinate',
        ],
        'data' => [
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
      ],
      'indexes' => array(
        'type' => array('type'),
        'id' => array('id'),
      ),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['type'] = DataDefinition::create('string')
      ->setLabel(t('Type'))
      ->setRequired(TRUE);

    $properties['id'] = DataDefinition::create('string')
      ->setLabel(t('Id'))
      ->setRequired(TRUE);

    $properties['status'] = DataDefinition::create('integer')
      ->setLabel(t('Status'))
      ->setRequired(TRUE);

    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Value'))
      ->setRequired(TRUE);

    $properties['lat'] = DataDefinition::create('float')
      ->setLabel(t('Latitude'))
      ->setReadOnly(TRUE);

    $properties['lng'] = DataDefinition::create('float')
      ->setLabel(t('Longitude'))
      ->setRequired(TRUE);

    $properties['data'] = MapDataDefinition::create()
      ->setLabel(t('Data'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'address_type' => 'adresse',
      'allow_non_unique_address' => 0,
      'allow_non_danish_address' => 0,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $element['address_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Dawa address type'),
      '#description' => $this->t('Choose "adresse" to Get address data, or "adgangsadresse" to get Adgangsadresse'),
      '#options' => [
        'adresse' => 'adresse',
        'adgangsadresse' => 'Adgangsadresse',
      ],
      '#default_value' => $this->getSetting('address_type'),
      '#multiple' => FALSE,
    ];
    // Setting to disable ADDRESS_MULTIPLE_LOCATION constraint.
    $element['allow_non_unique_address'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow non-unique addresses.'),
      '#description' => $this->t('This setting will disable Address multiple location constraint. You will be able to save addresses that might be resolved to multiple actual locations.'),
      '#default_value' => $this->getSetting('allow_non_unique_address'),
    ];

    // Setting to store non Danish addresses, by disabling
    // ADDRESS_CAN_NOT_BE_FOUND constraint.
    $element['allow_non_danish_address'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow storing non Danish addresses.'),
      '#description' => $this->t('This setting will allow you to store non Danish addresses. The address field will simply just be a textfield.'),
      '#default_value' => $this->getSetting('allow_non_danish_address'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'value';
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
  public function setValue($values, $notify = TRUE) {
    if (isset($values)) {
      $values += [
        'data' => [],
      ];
    }

    if (is_string($values['data'])) {
      $values['data'] = unserialize($values['data']);
    }
    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function getTextValue() {
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLat() {
    return $this->lat;
  }

  /**
   * {@inheritdoc}
   */
  public function getLng() {
    return $this->lng;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->data;
  }

}
