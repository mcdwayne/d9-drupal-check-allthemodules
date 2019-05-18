<?php

namespace Drupal\scheduled_message\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'scheduled_message' field type.
 *
 * @FieldType(
 *   id = "scheduled_message",
 *   label = @Translation("Scheduled message type"),
 *   description = @Translation("Scheduled Message field"),
 *   default_widget = "scheduled_message",
 *   default_formatter = "scheduled_message"
 * )
 */
class ScheduledMessage extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'target_type' => 'message_template',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'handler' => 'default',
      'handler_settings' => [],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['name'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Administrative name'))
      ->setRequired(TRUE);
    $properties['message_template_id'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Message Template to Send'))
      ->setRequired(TRUE);
    $properties['date_field'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Date Field to compare'))
      ->setRequired(TRUE);
    $properties['offset'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Offset message send by this amount'));
    $properties['active_state'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('State when this scheduled_email is active'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns'] += [
      'name' => [
        'type' => 'varchar',
        'length' => 90,
      ],
      'date_field' => [
        'description' => 'The date field on this type to use as the basis for this scheduled message.',
        'type' => 'varchar_ascii',
        'length' => 255,
      ],
      'offset' => [
        'description' => 'StrToTime offset from the date value field (e.g. -10 days).',
        'type' => 'varchar',
        'length' => 40,
      ],
      'active_state' => [
        'description' => 'The state when this message should get queued.',
        'type' => 'varchar',
        'length' => 20,
      ],
    ];
    $schema['indexes'] = [
      'name' => ['name'],
      'target_id' => ['target_id'],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    // TODO: Validate date field, active state.
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values = parent::generateSampleValue($field_definition);
    $random = new Random();
    $values['name'] = $random->word(mt_rand(1, $field_definition->getSetting('max_length')));
    $values['date_field'] = 'field_date.value';
    $values['offset'] = '';
    $values['active_state'] = 'active';
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = parent::storageSettingsForm($form, $form_state, $has_data);

    $elements['date_field'] = [
      '#type' => 'text',
      '#title' => t('Date field'),
      '#default_value' => $this->getSetting('date_field'),
      '#required' => TRUE,
      '#description' => t('The date field to use as the basis of the schedule.'),
      '#length' => 40,
      '#disabled' => $has_data,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('name')->getValue();
    return $value === NULL || $value === '' || parent::isEmpty();
  }

}
