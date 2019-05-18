<?php

namespace Drupal\entity_dispatcher\Manager;

use Drupal\entity_dispatcher\Event\BaseEntityEvent;
use Drupal\entity_dispatcher\Event\EntityEventInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class EntityDispatcherManager
 * @package Drupal\entity_dispatcher\Manager
 */
class EntityDispatcherManager {

  protected $eventDispatcher;

  /**
   * EntityDispatcherManager constructor.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   */
  public function __construct(EventDispatcherInterface $event_dispatcher) {
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Registers an event dispatcher for given entity.
   * @param \Drupal\entity_dispatcher\Event\EntityEventInterface $event
   * @return BaseEntityEvent
   */
  public function register(EntityEventInterface $event) {
    return $this->eventDispatcher->dispatch($event->getDispatcherType(), $event);
  }

}