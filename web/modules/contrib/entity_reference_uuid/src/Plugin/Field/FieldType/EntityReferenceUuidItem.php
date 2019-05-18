<?php

namespace Drupal\entity_reference_uuid\Plugin\Field\FieldType;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Field\PreconfiguredFieldUiOptionsInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\Core\TypedData\OptionsProviderInterface;
use Drupal\Core\Validation\Plugin\Validation\Constraint\AllowedValuesConstraint;

/**
 * Defines the 'entity_reference_uuid' entity field type.
 *
 * Supported settings (below the definition's 'settings' key) are:
 * - target_type: The entity type to reference. Required.
 *
 * @FieldType(
 *   id = "entity_reference_uuid",
 *   label = @Translation("Entity reference UUID"),
 *   description = @Translation("An entity field containing an entity reference by UUID."),
 *   category = @Translation("Reference"),
 *   default_widget = "entity_reference_autocomplete",
 *   default_formatter = "entity_reference_label",
 *   list_class = "\Drupal\entity_reference_uuid\EntityReferenceUuidFieldItemList",
 * )
 */
class EntityReferenceUuidItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $settings = $field_definition->getSettings();
    $target_type_info = \Drupal::entityManager()->getDefinition($settings['target_type']);

    $properties = parent::propertyDefinitions($field_definition);

    $target_uuid_definition = DataReferenceTargetDefinition::create('string')
      ->setLabel(new TranslatableMarkup('@label UUID', ['@label' => $target_type_info->getLabel()]));

    $target_uuid_definition->setRequired(TRUE);
    $properties['target_uuid'] = $target_uuid_definition;

    $properties['entity'] = DataReferenceDefinition::create('entity')
      ->setLabel($target_type_info->getLabel())
      ->setDescription(new TranslatableMarkup('The referenced entity by UUID'))
      // The entity object is computed out of the entity ID.
      ->setComputed(TRUE)
      ->setReadOnly(FALSE)
      ->setTargetDefinition(EntityDataDefinition::create($settings['target_type']))
      // We can add a constraint for the target entity type. The list of
      // referenceable bundles is a field setting, so the corresponding
      // constraint is added dynamically in ::getConstraints().
      ->addConstraint('EntityType', $settings['target_type']);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'target_uuid';
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $columns = [
      'target_uuid' => [
        'description' => 'The UUID of the target entity.',
        'type' => 'varchar_ascii',
        'length' => 128,
      ],
    ];

    $schema = [
      'columns' => $columns,
      'indexes' => [
        'target_uuid' => ['target_uuid'],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (isset($values) && !is_array($values)) {
      // If either a scalar or an object was passed as the value for the item,
      // assign it to the 'entity' or 'target_uuid' depending on values type.
      if (is_object($values)) {
        $this->set('entity', $values, $notify);
      }
      else {
        $this->set('target_uuid', $values, $notify);
      }
    }
    else {
      parent::setValue($values, FALSE);
      // Support setting the field item with only one property, but make sure
      // values stay in sync if only property is passed.
      // NULL is a valid value, so we use array_key_exists().
      if (is_array($values) && array_key_exists('target_uuid', $values) && !isset($values['entity'])) {
        $this->onChange('target_uuid', FALSE);
      }
      elseif (is_array($values) && !array_key_exists('target_uuid', $values) && isset($values['entity'])) {
        $this->onChange('entity', FALSE);
      }
      elseif (is_array($values) && array_key_exists('target_uuid', $values) && isset($values['entity'])) {
        // If both properties are passed, verify the passed values match. The
        // only exception we allow is when we have a new entity: in this case
        // its actual id and target_uuid will be different, due to the new entity
        // marker.
        $entity_uuid = $this->get('entity')->get('uuid');
        // If the entity has been saved and we're trying to set both the
        // target_uuid and the entity values with a non-null target UUID, then the
        // value for target_uuid should match the UUID of the entity value.
        if (!$this->entity->isNew() && $values['target_uuid'] !== NULL && ($entity_uuid !== $values['target_uuid'])) {
          throw new \InvalidArgumentException('The target UUID and entity passed to the entity reference item do not match.');
        }
      }
      // Notify the parent if necessary.
      if ($notify && $this->parent) {
        $this->parent->onChange($this->getName());
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $values = parent::getValue();

    // If there is an unsaved entity, return it as part of the field item values
    // to ensure idempotency of getValue() / setValue().
    if ($this->hasNewEntity()) {
      $values['entity'] = $this->entity;
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    // Make sure that the target UUID and the target property stay in sync.
    if ($property_name == 'entity') {
      $property = $this->get('entity');
      if ($target_uuid = $property->isTargetNew() ? NULL : $property->getValue()->uuid()) {
        $this->writePropertyValue('target_uuid', $target_uuid);
      }
    }
    elseif ($property_name == 'target_uuid') {
      $property = $this->get('entity');
      $entity_type = $property->getDataDefinition()->getConstraint('EntityType');
      $entities =  \Drupal::entityTypeManager()->getStorage($entity_type)->loadByProperties(['uuid' => $this->get('target_uuid')->getValue()]);
      if ($entity =  array_shift($entities)) {
        $this->writePropertyValue('target_id', $entity->id());
        $this->writePropertyValue('entity', $entity);
      }
    }
    parent::onChange($property_name, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    // Avoid loading the entity by first checking the 'target_uuid'.
    if ($this->target_uuid !== NULL) {
      return FALSE;
    }
    if ($this->entity && $this->entity instanceof EntityInterface) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    if ($this->hasNewEntity()) {
      // Save the entity if it has not already been saved by some other code.
      if ($this->entity->isNew()) {
        $this->entity->save();
      }
      // Make sure the parent knows we are updating this property so it can
      // react properly.
      $this->target_uuid = $this->entity->uuid();
    }
    if (!$this->isEmpty() && $this->target_uuid === NULL) {
      $this->target_uuid = $this->entity->uuid();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $manager = \Drupal::service('plugin.manager.entity_reference_selection');

    // Instead of calling $manager->getSelectionHandler($field_definition)
    // replicate the behavior to be able to override the sorting settings.
    $options = [
      'target_type' => $field_definition->getFieldStorageDefinition()->getSetting('target_type'),
      'handler' => $field_definition->getSetting('handler'),
      'handler_settings' => $field_definition->getSetting('handler_settings') ?: [],
      'entity' => NULL,
    ];

    $entity_type = \Drupal::entityManager()->getDefinition($options['target_type']);
    $options['handler_settings']['sort'] = [
      'field' => $entity_type->getKey('uuid'),
      'direction' => 'DESC',
    ];
    $selection_handler = $manager->getInstance($options);

    // Select a random number of references between the last 50 referenceable
    // entities created.
    if ($referenceable = $selection_handler->getReferenceableEntities(NULL, 'CONTAINS', 50)) {
      $group = array_rand($referenceable);
      $values['target_uuid'] = array_rand($referenceable[$group]);
      return $values;
    }
  }

  /**
   * Determines whether the item holds an unsaved entity.
   *
   * This is notably used for "autocreate" widgets, and more generally to
   * support referencing freshly created entities (they will get saved
   * automatically as the hosting entity gets saved).
   *
   * @return bool
   *   TRUE if the item holds an unsaved entity.
   */
  public function hasNewEntity() {
    return !$this->isEmpty() && $this->target_uuid === NULL && $this->entity->isNew();
  }

  /**
   * {@inheritdoc}
   */
  public static function getPreconfiguredOptions() {
    $options = [];

    // Add all the commonly referenced entity types as distinct pre-configured
    // options.
    $entity_types = \Drupal::entityManager()->getDefinitions();
    $common_references = array_filter($entity_types, function (EntityTypeInterface $entity_type) {
      return $entity_type->isCommonReferenceTarget();
    });

    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
    foreach ($common_references as $entity_type) {
      $options[$entity_type->id()] = [
        'label' => $entity_type->getLabel() . ' ' . t('by UUID'),
        'field_storage_config' => [
          'settings' => [
            'target_type' => $entity_type->id(),
          ]
        ]
      ];
    }

    return $options;
  }

}
