<?php

namespace Drupal\session_entity;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service to get the current user's session entity.
 */
class CurrentSessionEntity {

  /**
   * The entity storage for session entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|object
   */
  protected $sessionEntityStorage;

  /**
   * Constructs a new instance of the CurrentSessionEntity.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type plugin manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->sessionEntityStorage = $entity_type_manager->getStorage('session');
  }

  /**
   * Get the session entity for the current user.
   *
   * @return \Drupal\session_entity\Entity\Session|NULL
   *   The session entity for the current user, or NULL if the current user does
   *   not have one.
   */
  public function getCurrentUserSessionEntity() {
    return $this->sessionEntityStorage->load(NULL);
  }

}
