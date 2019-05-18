<?php

/**
 * @file
 * Contains \Drupal\demo_content\DemoContentManager.
 */

namespace Drupal\demo_content;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\AliasStorageInterface;

/**
 * The DemoContentManager provides helper functions for importing demo content.
 */
class DemoContentManager implements DemoContentManagerInterface {

  /**
   * @var \Drupal\demo_content\DemoContentExtensionManagerInterface;
   */
  protected $extensionManager;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity field manager
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity repository
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The file manager
   *
   * @var \Drupal\demo_content\DemoContentFileManager
   */
  protected $fileManager;

  /**
   * @var \Drupal\demo_content\DemoContentEntityTypeValidatorInterface
   */
  protected $entityTypeValidator;

  /**
   * @var \Drupal\demo_content\AliasStorageInterface
   */
  protected $aliasStorage;

  public function __construct(DemoContentExtensionManagerInterface $extension_manager, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityRepositoryInterface $entity_repository ,EntityFieldManagerInterface $entity_field_manager, DemoContentEntityTypeValidatorInterface $entity_type_validator, DemoContentFileManager $file_manager, AliasStorageInterface $alias_storage) {
    $this->extensionManager = $extension_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityRepository = $entity_repository;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeValidator = $entity_type_validator;
    $this->fileManager = $file_manager;
    $this->aliasStorage = $alias_storage;
  }

  /**
   * Helper to import demo content from an extension.
   *
   * @param string $extension_name
   *  The name of the extension.
   * @return array
   *  An array of created entities.
   */
  public function importFromExtension($extension_name) {
    $entities = [];
    $extension = $this->extensionManager->getExtension($extension_name);
    if ($extension) {
      foreach ($extension->info['demo_content'] as $content_info) {
        $entities = $this->import($content_info);
      }
    }
    return $entities;
  }

  /**
   * @inheritDoc
   */
  public function import(array $content_info) {
    $entities = [];
    $entity_type_id = $content_info['entity_type'];
    $bundle = $content_info['bundle'];

    // Check if invalid content entity type.
    if (!$this->entityTypeValidator->isContentEntityType($entity_type_id)) {
      throw new \Exception(sprintf('The "%s" entity type is not a valid content entity.', $entity_type_id));
    }

    $content = $content_info['content'];
    foreach ($content as &$entity_info) {

      // Handle files.
      if ($entity_type_id == 'file') {
        // Check if file exists.
        $entity = $this->entityRepository->loadEntityByUuid($entity_type_id, $entity_info['uuid']);

        if (!empty($entity)) {
          // Update the file.
          $entity = $this->fileManager->update($entity, $entity_info);
        }
        else {
          // Create the file.
          $entity = $this->fileManager->create($entity_info);
        }

        // Save the file.
        $entities[] = $entity;
        continue;
      }

      $this->processEntityReferences($entity_type_id, $bundle, $entity_info);

      // Add bundle to $content.
      $bundle_id = $this->getBundleIdForEntityType($entity_type_id);
      $entity_info[$bundle_id] = $bundle;

      // Get the path alias from entity info.
      if (isset($entity_info['path_alias'])) {
        $path_alias = $entity_info['path_alias'];
        unset($entity_info['path_alias']);
      }

      // TODO: Refactor this to a method.
      // Check if entity exists.
      $entity = $this->entityRepository->loadEntityByUuid($entity_type_id, $entity_info['uuid']);
      if (!empty($entity)) {
        foreach ($entity_info as $key => $value) {
          $entity->set($key, $value);
        }
      }
      else {
      // Create an entity from $content.
      $entity = $this->entityTypeManager->getStorage($entity_type_id)
        ->create($entity_info);
      }

      // Save the entity.
      $entity->save();

      if ($entity->id()) {
        $entities[] = $entity;

        // Create the path alias if set.
        if (isset($path_alias)) {
          $source = '/' . $entity->toUrl()->getInternalPath();
          $this->aliasStorage->save($source, $path_alias);
        }

      }
    }

    return $entities;
  }

  /**
   * Checks if a field is a reference field.
   *
   * @param $entity_type_id
   *  The entity_type_id for the field.
   * @param $bundle
   *  The bundle name.
   * @param $field_name
   *  The name of the field.
   * @return bool TRUE if $field_name is a reference field.
   * TRUE if $field_name is a reference field.
   */
  protected function isEntityReferenceField($entity_type_id, $bundle, $field_name) {
    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    if (isset($fields[$field_name])) {
      $reflection = new \ReflectionClass($fields[$field_name]->getClass());
      return $reflection->implementsInterface('\Drupal\Core\Field\EntityReferenceFieldItemListInterface');
    }
    return FALSE;
  }


  /**
   * Returns the bundle id for an entity type.
   *
   * @param $entity_type_id
   *  The entity type id.
   * @return string
   *  The bundle id for the entity type.
   */
  protected function getBundleIdForEntityType($entity_type_id) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    if ($entity_type) {
      return $entity_type->getKey('bundle');
    }
    return 'type';
  }

  /**
   * Converts entity reference field values from UUIDs to primary keys.
   *
   * The referenced entities must already exist.
   *
   * @param string $entity_type_id
   *   The entity type ID of the entity;
   * @param string $bundle
   *   The entity's bundle;
   * @param array $entity_info
   *   The entity information array containing the import field values.
   */
  protected function processEntityReferences($entity_type_id, $bundle, &$entity_info) {
    foreach ($entity_info as $field_name => $field_values) {

      // Find entity reference fields and get target_id from uuid.
      if ($this->isEntityReferenceField($entity_type_id, $bundle, $field_name)) {
        // Clear up the field_name, we'll populate the target_ids.
        $entity_info[$field_name] = [];

        // Load reference entities from the field_values.
        // Single field item.
        if (isset($field_values['entity_type']) && isset($field_values['uuid'])) {
          $value = $this->processEntityReferenceValue($field_values);
          if ($value) {
            $entity_info[$field_name][] = $value;
          }
        }
        else {
          // Multiple field items.
          foreach ($field_values as $referenced_entity_value) {
            $value = $this->processEntityReferenceValue($referenced_entity_value);
            if ($value) {
              $entity_info[$field_name][] = $value;
            }
          }
        }
      }
    }
  }

  /**
   * Converts an entity reference value from UUID to primary key.
   *
   * If the target entity is revisionable, the revision ID will be added to the
   * field value.
   *
   * @param array $value
   *   The import field value array.
   *
   * @return array
   *   An associative array of values in the right format for saving, keyed by:
   *   - target_id: the entity ID.
   */
  protected function processEntityReferenceValue($value) {
    $referenced_entity = $this->entityRepository->loadEntityByUuid($value['entity_type'], $value['uuid']);
    if ($referenced_entity) {
      $ret = ['target_id' => $referenced_entity->id()];
      if ($referenced_entity->getEntityType()->isRevisionable()) {
        assert('$referenced_entity instanceof \Drupal\Core\Entity\RevisionableInterface');
        $ret['target_revision_id'] = $referenced_entity->getRevisionId();
      }
      return $ret;
    }
  }
}
