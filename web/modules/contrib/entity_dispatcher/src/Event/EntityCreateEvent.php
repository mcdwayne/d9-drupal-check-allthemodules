<?php

namespace Drupal\entity_dispatcher\Event;

use Drupal\entity_dispatcher\EntityDispatcherEvents;

/**
 * Class EntityCreateEvent
 * @package Drupal\entity_dispatcher\Event
 */
class EntityCreateEvent extends BaseEntityEvent {

  /**
   * @inheritdoc.
   */
  public function getDispatcherType() {
    return EntityDispatcherEvents::ENTITY_CREATE;
  }
}