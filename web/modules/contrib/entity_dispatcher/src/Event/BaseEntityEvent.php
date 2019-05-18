<?php

namespace Drupal\entity_dispatcher\Event;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class BaseEntityEvent
 * @package Drupal\entity_dispatcher\Event
 */
abstract class BaseEntityEvent extends Event implements  EntityEventInterface{

  protected $entity;

  /**
   * BaseEntityEvent constructor.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function __construct(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
  }

}