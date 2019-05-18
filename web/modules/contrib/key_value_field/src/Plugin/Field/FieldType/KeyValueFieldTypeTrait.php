<?php

namespace Drupal\key_value_field\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationWrapper;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Common traits for key value field types which inherit different field types.
 */
trait KeyValueFieldTypeTrait {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    // Get the schema from the big text field.
    $schema = parent::schema($field_definition);
    // Add an index for key.
    $schema['indexes']['key'] = ['key'];
    // Add the key and description fields.
    $schema['columns'] += [
      'key' => [
        'description' => 'Stores the "Key" value.',
        'type' => $field_definition->getSetting('key_is_ascii') === TRUE ? 'varchar_ascii' : 'varchar',
        'length' => (int) $field_definition->getSetting('key_max_length'),
      ],
      // Add the description db column.
      'description' => [
        'description' => 'Stores an optional description of the field.',
        'type' => 'varchar',
        'length' => 255,
        'not null' => FALSE,
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    return [
      // Add the property definition for the key field.
      'key' => DataDefinition::create('string')
        ->setLabel(new TranslationWrapper('Key'))
        ->setRequired(TRUE),
      // Add the property definition for the description field.
      'description' => DataDefinition::create('string')
        ->setLabel(new TranslationWrapper('Description'))
        ->setRequired(FALSE),
    ] + parent::propertyDefinitions($field_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'key_max_length' => 255,
      'key_is_ascii' => FALSE,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {

    // Add the key length form element.
    return [
      // Add a form element for the max length of the key element.
      'key_max_length' => [
        '#type' => 'number',
        '#title' => t('Key maximum length'),
        '#default_value' => $this->getSetting('key_max_length'),
        '#required' => TRUE,
        '#description' => t('The maximum length of the "key" field in characters.'),
        '#min' => 1,
        '#disabled' => $has_data,
      ],
    ] + parent::storageSettingsForm($form, $form_state, $has_data);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $key = $this->get('key')->getValue();
    return ($key === NULL || $key === '') && parent::isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // Create a random data generator.
    $random = new Random();

    return [
      // Add a random key.
      'key' => $random->word(mt_rand(1, $field_definition->getSetting('key_max_length'))),
      // Add a random description.
      // @todo make sure the description is enabled before generating a value.
      'description' => $random->word(mt_rand(1, 255)),
    ] + parent::generateSampleValue($field_definition);
  }

}
