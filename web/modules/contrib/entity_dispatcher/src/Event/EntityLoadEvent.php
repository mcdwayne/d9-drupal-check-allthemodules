<?php

namespace Drupal\entity_dispatcher\Event;

use Drupal\entity_dispatcher\EntityDispatcherEvents;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class EntityLoadEvent
 * @package Drupal\entity_dispatcher\Event
 */
class EntityLoadEvent extends Event implements EntityEventInterface {

  protected $entities;
  protected $entityTypeId;

  /**
   * EntityLoadEvent constructor.
   * @param array $entities
   * @param $entity_type_id
   */
  public function __construct(array $entities, $entity_type_id) {
    $this->entities = $entities;
    $this->entityType = $entity_type_id;
  }

  /**
   * @return array
   */
  public function getEntities() {
    return $this->entities;
  }

  /**
   * @return mixed
   */
  public function getEntityTypeId() {
    return $this->entityTypeId;
  }

  /**
   * @inheritdoc.
   */
  public function getDispatcherType() {
    return EntityDispatcherEvents::ENTITY_LOAD;
  }
}