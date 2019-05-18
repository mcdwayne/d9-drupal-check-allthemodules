<?php

namespace Drupal\entity_dispatcher\Event;

use Drupal\entity_dispatcher\EntityDispatcherEvents;

/**
 * Class EntityInsertEvent
 * @package Drupal\entity_dispatcher\Event
 */
class EntityInsertEvent extends BaseEntityEvent {

  /**
   * @inheritdoc.
   */
  public function getDispatcherType() {
    return EntityDispatcherEvents::ENTITY_INSERT;
  }
}