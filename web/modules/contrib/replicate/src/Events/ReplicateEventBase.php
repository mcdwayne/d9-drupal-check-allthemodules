<?php

namespace Drupal\replicate\Events;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

abstract class ReplicateEventBase extends Event {

  /**
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  public function __construct(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getEntity() {
    return $this->entity;
  }

}
