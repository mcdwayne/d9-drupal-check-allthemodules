<?php

namespace Drupal\br_address_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'br_address_field_type' field type.
 *
 * @FieldType(
 *   id = "br_address_field_type",
 *   label = @Translation("Brazilian address"),
 *   description = @Translation("Brazilian Address"),
 *   default_widget = "br_address_widget_type",
 *   default_formatter = "br_address_plain_formatter"
 * )
 */
class BrAddressFieldType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'max_length' => 255,
      'is_ascii' => FALSE,
      'case_sensitive' => FALSE,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties = [];
    $properties['postal_code'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Postal code'))
      ->setRequired(TRUE);
    $properties['thoroughfare'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Thoroughfare'))
      ->setRequired(TRUE);
    $properties['number'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Number'))
      ->setRequired(TRUE);
    $properties['street_complement'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Complement'))
      ->setRequired(FALSE);
    $properties['neighborhood'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Neighborhood'))
      ->setRequired(TRUE);
    $properties['city'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('City'))
      ->setRequired(TRUE);
    $properties['state'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('State'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'postal_code' => [
          'type' => 'varchar',
          'length' => 10,
        ],
        'thoroughfare' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'number' => [
          'type' => 'varchar',
          'length' => 10,
        ],
        'street_complement' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
        'neighborhood' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'city' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'state' => [
          'type' => 'varchar',
          'length' => 2,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];

    $elements['consult_postal_code'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fill address'),
      '#description' => $this->t('Auto fill address by postal code field.'),
      '#default_value' => $this->getSetting('consult_postal_code'),
      '#required' => FALSE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $postal_code = empty($this->get('postal_code')->getValue());
    $thoroughfare = empty($this->get('thoroughfare')->getValue());
    $number = empty($this->get('number')->getValue());
    $neighborhood = empty($this->get('neighborhood')->getValue());
    $city = empty($this->get('city')->getValue());
    $state = empty($this->get('state')->getValue());
    return $postal_code || $thoroughfare || $number || $neighborhood || $city || $state;
  }

}
