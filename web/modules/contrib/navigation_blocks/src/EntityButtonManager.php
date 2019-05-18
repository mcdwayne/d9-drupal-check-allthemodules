<?php

namespace Drupal\navigation_blocks;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Manager for entity back buttons.
 *
 * @package Drupal\navigation_blocks
 */
class EntityButtonManager implements EntityButtonManagerInterface {

  use StringTranslationTrait;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a manager for entity back buttons.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   Entity Type Bundle Info.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Entity Field Manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo, EntityFieldManagerInterface $entityFieldManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityReferenceFieldOptions(EntityTypeInterface $entityType): array {
    $options = [];

    $bundles = array_keys($this->entityTypeBundleInfo->getBundleInfo($entityType->id()));
    foreach ($bundles as $bundle) {
      /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $fields */
      $fields = $this->entityFieldManager->getFieldDefinitions($entityType->id(), $bundle);
      foreach ($fields as $field) {
        if ($field->getType() !== 'entity_reference') {
          continue;
        }
        $options[$field->getName()] = $field->getLabel();
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType($entityTypeId): EntityTypeInterface {
    return $this->entityTypeManager->getDefinition($entityTypeId);
  }

  /**
   * {@inheritdoc}
   */
  public function getReferencedEntity(ContentEntityInterface $entity, string $fieldName): EntityInterface {
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $entityReferenceFieldItemList */
    $entityReferenceFieldItemList = $entities = $entity->get($fieldName);
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $entities */
    $entities = $entityReferenceFieldItemList->referencedEntities();
    return reset($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function getReversedEntityReferenceEntity(EntityInterface $entity, string $reversedEntityTypeId, string $reversedBundle, string $reversedFieldName): EntityInterface {
    $reversedEntityType = $this->getEntityType($reversedEntityTypeId);
    $entityType = $entity->getEntityType();
    $properties = [
      $reversedFieldName . '.entity.' . $entityType->getKey('id') => $entity->id(),
    ];

    if (!empty($reversedBundle)) {
      $properties[$reversedEntityType->getKey('bundle')] = $reversedBundle;
    }

    $entities = $this->entityTypeManager->getStorage($reversedEntityTypeId)
      ->loadByProperties($properties);
    return reset($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function getReversedEntityReferenceFieldOptions(EntityTypeInterface $entityType): array {
    /** @var \Drupal\field\FieldStorageConfigInterface[] $referenceFields */
    $referenceFields = $this->entityTypeManager->getStorage('field_storage_config')
      ->loadByProperties(
        [
          'settings' => [
            'target_type' => $entityType->id(),
          ],
          'type' => 'entity_reference',
          'deleted' => FALSE,
          'status' => 1,
        ]
      );

    $options = [];
    foreach ($referenceFields as $referenceField) {
      $fieldEntityType = $this->getEntityType($referenceField->getTargetEntityTypeId());
      $fieldName = $referenceField->getName();

      /** @var \Drupal\Core\Field\FieldConfigInterface[] $fieldInstances */
      $fieldInstances = $this->entityTypeManager->getStorage('field_config')
        ->loadByProperties(
          [
            'field_name' => $referenceField->getName(),
            'entity_type' => $fieldEntityType->id(),
          ]
        );

      foreach ($fieldInstances as $fieldInstance) {
        $bundle = $fieldInstance->getTargetBundle();
        $options[$fieldEntityType->id() . ':' . $bundle . ':' . $fieldName] = $fieldEntityType->getLabel() . ' (' . $bundle . ') : ' . $fieldInstance->label();
      }
    }

    return $options;
  }

}
