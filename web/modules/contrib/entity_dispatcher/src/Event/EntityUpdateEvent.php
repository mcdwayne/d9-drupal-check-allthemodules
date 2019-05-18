<?php

namespace Drupal\entity_dispatcher\Event;

use Drupal\entity_dispatcher\EntityDispatcherEvents;

/**
 * Class EntityUpdateEvent
 * @package Drupal\entity_dispatcher\Event
 */
class EntityUpdateEvent extends BaseEntityEvent {

  /**
   * @inheritdoc.
   */
  public function getDispatcherType() {
    return EntityDispatcherEvents::ENTITY_UPDATE;
  }
}