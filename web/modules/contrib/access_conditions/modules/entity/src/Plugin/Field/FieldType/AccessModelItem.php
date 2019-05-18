<?php

namespace Drupal\access_conditions_entity\Plugin\Field\FieldType;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;

/**
 * Implementation of the 'access_model_reference' field type.
 *
 * @FieldType(
 *   id = "access_model_reference",
 *   label = @Translation("Access conditions"),
 *   description = @Translation("Stores access models reference and access operation."),
 *   default_widget = "access_model_reference_autocomplete_widget",
 *   default_formatter = "access_model_reference_formatter",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList"
 * )
 */
class AccessModelItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return ['target_type' => 'access_model'] +
      parent::defaultStorageSettings();
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
    $target_type_info = \Drupal::entityTypeManager()->getDefinition('access_model');

    $properties['target_id'] = DataReferenceTargetDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Restrict access to the following user models'))
      ->setRequired(TRUE);
    $properties['entity'] = DataReferenceDefinition::create('entity')
      ->setLabel($target_type_info->getLabel())
      ->setDescription(new TranslatableMarkup('The referenced user model'))
      ->setComputed(TRUE)
      ->setReadOnly(FALSE)
      ->setTargetDefinition(EntityDataDefinition::create('access_model'))
      ->addConstraint('EntityType', 'access_model');

    $properties['operation'] = DataDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('The access operation'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $columns = [
      'target_id' => [
        'description' => 'The ID of the target entity.',
        'type' => 'varchar_ascii',
        'length' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      ],
      'operation' => [
        'type' => 'int',
        'size' => 'tiny',
      ],
    ];

    $schema = [
      'columns' => $columns,
      'indexes' => [
        'target_id' => ['target_id'],
        'operation' => ['operation'],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values = parent::generateSampleValue($field_definition);
    $values['operation'] = mt_rand(0, 2);

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];

    $elements['target_type'] = [
      '#type' => 'value',
      '#value' => $this->getSetting('target_type'),
    ];

    return $elements;
  }

}
