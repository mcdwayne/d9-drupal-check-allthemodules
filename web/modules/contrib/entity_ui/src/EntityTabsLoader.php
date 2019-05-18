<?php

namespace Drupal\entity_ui;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Loads entity tabs.
 */
class EntityTabsLoader {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityTabsLoader.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Loads the entity tabs for a single target entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $target_entity_type
   *  The target entity type to load tabs for.
   *
   * @return
   *  An array of entity tab entities, keyed by ID.
   */
  public function getEntityTabs(EntityTypeInterface $target_entity_type) {
    $storage = $this->entityTypeManager->getStorage('entity_tab');

    $query = $storage->getQuery();
    $query->condition('target_entity_type', $target_entity_type->id());
    $ids = $query->execute();

    $tabs = $storage->loadMultiple($ids);

    return $tabs;
  }

}
