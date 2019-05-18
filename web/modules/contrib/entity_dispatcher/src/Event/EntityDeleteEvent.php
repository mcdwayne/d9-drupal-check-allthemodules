<?php

namespace Drupal\entity_dispatcher\Event;

use Drupal\entity_dispatcher\EntityDispatcherEvents;

/**
 * Class EntityDeleteEvent
 * @package Drupal\entity_dispatcher\Event
 */
class EntityDeleteEvent extends BaseEntityEvent {

  /**
   * @inheritdoc.
   */
  public function getDispatcherType() {
    return EntityDispatcherEvents::ENTITY_DELETE;
  }
}