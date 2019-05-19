<?php

namespace Drupal\webfactory;

use Drupal\Core\Entity\Entity;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class EntityExportEvent.
 */
class EntityExportEvent extends Event {

  /**
   * Event name.
   */
  const EVENT_NAME = 'webfactory.entity.export';

  /**
   * Entity.
   *
   * @var array
   */
  protected $entity;

  /**
   * EntityExportEvent constructor.
   *
   * @param Entity $entity
   *   Entity.
   */
  public function __construct(Entity $entity) {
    $this->entity = $entity;
  }

  /**
   * Get entity.
   *
   * @return Entity
   *   Entity.
   */
  public function getEntity() {
    return $this->entity;
  }

}
