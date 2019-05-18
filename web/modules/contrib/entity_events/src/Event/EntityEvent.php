<?php

namespace Drupal\entity_events\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_events\EntityEventType;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class to contain an entity event.
 */
class EntityEvent extends Event {

  /**
   * The Entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  private $entity;

  /**
   * The event type.
   *
   * @var \Drupal\entity_events\EntityEventType
   */
  private $eventType;

  /**
   * Construct a new entity event.
   *
   * @param string $event_type
   *   The event type.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity which caused the event.
   */
  public function __construct($event_type, EntityInterface $entity) {
    $this->entity = $entity;
    $this->eventType = $event_type;
  }

  /**
   * Method to get the entity from the event.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Method to get the event type.
   */
  public function getEventType() {
    return $this->eventType;
  }

}
