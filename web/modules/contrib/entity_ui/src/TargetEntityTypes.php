<?php

namespace Drupal\entity_ui;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Determines which entity types which are suitable as entity tab targets.
 *
 * This serves to keep the definition of what entity types may be entity tab
 * targets in a single place.
 */
class TargetEntityTypes {

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
   * Gets entity types which are suitable as entity tab targets.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   An array of entity types, keyed by their ID.
   */
  public function getTargetEntityTypes() {
    $entity_types = $this->entityTypeManager->getDefinitions();
    $target_entity_types = $this->filterTargetEntityTypes($entity_types);
    return $target_entity_types;
  }

  /**
   * Filters entity types to those which are suitable as entity tab targets.
   *
   * This only relies on the given entity type objects, so is safe to be called
   * during the entity type rebuild process.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   An array of entity types, keyed by their ID.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   An array of entity types, keyed by their ID.
   */
  public function filterTargetEntityTypes($entity_types) {
    $target_entity_types = [];

    foreach ($entity_types as $entity_type_id => $entity_type) {
      if ($entity_type->getGroup() != 'content') {
        // We only work with content entities.
        continue;
      }

      if (!$entity_type->hasLinkTemplate('canonical')) {
        // We only work with entities that have a canonical link template.
        continue;
      }

      $target_entity_types[$entity_type_id] = $entity_type;
    }

    return $target_entity_types;
  }

}
