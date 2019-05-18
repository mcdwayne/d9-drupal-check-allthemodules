<?php

namespace Drupal\role_log\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityInterface;

/**
 * Fire an event that we can subscribe to when a user entity is saved.
 */
class UserPresaveEvent extends Event {

  const USER_PRESAVE = 'role_log.user.presave';

  /**
   * User entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  private $entity;

  /**
   * Construct a new event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A Drupal entity.
   */
  public function __construct(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * Get the entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A Drupal entity.
   */
  public function getUser() {

    return $this->entity;
  }

}
