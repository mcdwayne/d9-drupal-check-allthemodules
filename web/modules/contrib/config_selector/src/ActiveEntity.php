<?php

namespace Drupal\config_selector;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Helper for getting the active entity or ID of any config_selector feature.
 */
class ActiveEntity {
  use ConfigSelectorSortTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ActiveEntity constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Gets the active entity from the entity type and config_selector feature.
   *
   * @param string $entity_type_id
   *   The entity type to get the ID for.
   * @param string $feature
   *   The config selector feature to get the ID for.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The active entity for the provided entity type and feature. NULL is there
   *   is no corresponding entity.
   */
  public function get($entity_type_id, $feature) {
    $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
    $matching_config = $entity_storage
      ->getQuery()
      ->condition('third_party_settings.config_selector.feature', $feature)
      ->condition('status', FALSE, '<>')
      ->execute();
    $config_entities = $entity_storage->loadMultiple($matching_config);
    $config_entities = $this->sortConfigEntities($config_entities);
    return array_pop($config_entities);
  }

  /**
   * Gets the active entity from using the details from an entity.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The entity to get the active entity for.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The active entity for the provided entity.
   */
  public function getFromEntity(ConfigEntityInterface $entity) {
    $feature = $entity->getThirdPartySetting('config_selector', 'feature');
    if (!$feature) {
      // This is not a config selected entity. Therefore, do not convert the ID.
      return $entity;
    }
    $active_entity = $this->get($entity->getEntityTypeId(), $feature);
    // If there is no active ID return the entity.
    return $active_entity ?: $entity;
  }

}
