<?php

namespace Drupal\entity_collector;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_collector\Entity\EntityCollectionTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityCollectionSourceFieldManager
 *
 * @package Drupal\entity_collector
 */
class EntityCollectionSourceFieldManager {

  /**
   * Contains the entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Contains the entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity field manager service.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entityTypeManager) {
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Add the entity reference field to the Config entity with the reference to
   * the entity based on the source of the entity collection type.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType
   *    Contains the entity collection type entity.
   *
   * @return \Drupal\Core\Field\FieldConfigInterface|\Drupal\Core\Field\FieldDefinitionInterface|null
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addEntitiesField(EntityCollectionTypeInterface $entityCollectionType) {
    $source = $entityCollectionType->getSource();
    $sourceField = $this->getSourceFieldDefinition($entityCollectionType, $source);
    if ($sourceField) {
      return $sourceField;
    }

    $sourceField = $this->createSourceField($entityCollectionType, $source);
    /** @var \Drupal\field\FieldStorageConfigInterface $storage */
    $storage = $sourceField->getFieldStorageDefinition();
    if ($storage->isNew()) {
      $storage->setSetting('target_type', $source);
      $storage->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
      $storage->save();
    }
    $sourceField->save();

    $fieldName = $sourceField->getName();
    $fieldType = $sourceField->getType();

    if ($sourceField->isDisplayConfigurable('form')) {
      $component = \Drupal::service('plugin.manager.field.widget')
        ->prepareConfiguration($fieldType, []);
      try {
        entity_get_form_display('entity_collection', $entityCollectionType->id(), 'default')
          ->setComponent($fieldName, $component)
          ->save();
      }
      catch (EntityStorageException $storageException) {
        throw new $storageException;
      }
    }

    if ($sourceField->isDisplayConfigurable('view')) {
      $component = \Drupal::service('plugin.manager.field.formatter')
        ->prepareConfiguration($fieldName, []);

      try {
        entity_get_display('entity_collection', $entityCollectionType->id(), 'default')
          ->setComponent($fieldName, $component)
          ->save();
      }
      catch (EntityStorageException $storageException) {
        throw new $storageException;
      }
    }

    return $sourceField;
  }

  /**
   * Creates the source field storage definition.
   *
   * By default, the first field type listed in the plugin definition's
   * allowed_field_types array will be the generated field's type.
   *
   * @param string $source
   *   Contains the source of the collection to reference to.
   *
   * @return \Drupal\field\FieldStorageConfigInterface
   *   The unsaved field storage definition.
   */
  private function createSourceFieldStorage($source) {
    /** @var \Drupal\field\FieldStorageConfigInterface $fieldStorageConfig */
    $fieldStorageConfig = $this->entityTypeManager
      ->getStorage('field_storage_config')
      ->create([
        'entity_type' => 'entity_collection',
        'field_name' => $this->getSourceFieldName($source),
        'type' => 'entity_reference',
      ]);
    return $fieldStorageConfig;
  }

  /**
   * Returns the source field storage definition.
   *
   * @param string $source
   *   Contains the source of the collection to reference to.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface|null
   *   The field storage definition or NULL if it doesn't exists.
   */
  private function getSourceFieldStorage($source) {
    $field = 'field_collection_entities_' . $source;
    if ($field) {
      $fields = $this->entityFieldManager->getFieldStorageDefinitions('entity_collection');
      return isset($fields[$field]) ? $fields[$field] : NULL;
    }
    return NULL;
  }

  /**
   * Returns the source field definition.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType
   *   Entity Collection Type.
   * @param string $source
   *   Contains the source of the collection to reference to.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface|null
   *   The field definition or NULL if it doesn't exists.
   */
  public function getSourceFieldDefinition(EntityCollectionTypeInterface $entityCollectionType, $source) {
    $field = 'field_collection_entities_' . $source;
    if ($field) {
      $fields = $this->entityFieldManager->getFieldDefinitions('entity_collection', $entityCollectionType->id());
      return isset($fields[$field]) ? $fields[$field] : NULL;
    }
    return NULL;
  }

  /**
   * Create the source field for the entity collection type.
   *
   * @param \Drupal\entity_collector\Entity\EntityCollectionTypeInterface $entityCollectionType
   *   Entity Collection Type.
   * @param string $source
   *   Contains the source of the collection to reference to.
   *
   * @return \Drupal\Core\Field\FieldConfigInterface|null
   *   The field definition or NULL if it doesn't exists.
   */
  private function createSourceField(EntityCollectionTypeInterface $entityCollectionType, $source) {
    $storage = $this->getSourceFieldStorage($source) ?: $this->createSourceFieldStorage($source);
    /** @var \Drupal\Core\Field\FieldConfigInterface $fieldDefinition */
    $fieldDefinition = $this->entityTypeManager
      ->getStorage('field_config')
      ->create([
        'field_storage' => $storage,
        'bundle' => $entityCollectionType->id(),
        'label' => 'Entities',
        'required' => TRUE,
      ]);

    return $fieldDefinition;
  }

  /**
   * Determine the name of the source field.
   *
   * @param string $source
   *   Contains the source of the collection to reference to.
   *
   * @return string
   *   The source field name. If one is already stored in configuration, it is
   *   returned. Otherwise, a new, unused one is generated.
   */
  private function getSourceFieldName($source) {
    $baseId = 'field_collection_entities_' . $source;
    $tries = 0;
    $storage = $this->entityTypeManager->getStorage('field_storage_config');

    // Iterate at least once, until no field with the generated ID is found.
    do {
      $id = $baseId;
      // If we've tried before, increment and append the suffix.
      if ($tries) {
        $id .= '_' . $tries;
      }
      $field = $storage->load('entity_collection.' . $id);
      $tries++;
    } while ($field);

    return $id;
  }
}