<?php

namespace Drupal\checklist_entity_reference\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * Defines the 'entity_reference' entity field type.
 *
 * Supported settings (below the definition's 'settings' key) are:
 * - target_type: The entity type to reference. Required.
 *
 * @FieldType(
 *   id = "entity_reference_checklist",
 *   label = @Translation("Entity reference (checklist)"),
 *   description = @Translation("An entity field containing an entity reference."),
 *   category = @Translation("Reference"),
 *   default_widget = "entity_reference_checklist_options",
 *   default_formatter = "entity_reference_label",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 * )
 */
class EntityReferenceChecklistItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'checked_date' => '',
      'checked_user' => '',
      'target_type' => 'taxonomy_term',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    $schema['columns']['checked_date'] = [
      'description' => 'The timestamp of the date the item was checked.',
      'type' => 'int',
      'unsigned' => TRUE,
    ];
    $schema['columns']['checked_by'] = [
      'description' => 'The ID of the checking user entity.',
      'type' => 'int',
      'unsigned' => TRUE,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['checked_date'] = DataDefinition::create('timestamp')
      ->setLabel(t('Timestamp Checked'))
      ->setRequired(FALSE);

    $properties['checked_by'] = DataReferenceTargetDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('@label ID', ['@label' => "Update By"]))
      ->setSetting('unsigned', TRUE)
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();
    if (!$this->isEmpty() && !$this->checked_date) {
      $this->checked_date = time();
      $this->checked_by = \Drupal::currentUser()->id();
    }
  }

}
