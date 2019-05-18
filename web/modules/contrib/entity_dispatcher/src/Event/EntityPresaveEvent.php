<?php

namespace Drupal\entity_dispatcher\Event;

use Drupal\entity_dispatcher\EntityDispatcherEvents;

/**
 * Class EntityPresaveEvent
 * @package Drupal\entity_dispatcher\Event
 */
class EntityPresaveEvent extends BaseEntityEvent {

  /**
   * @inheritdoc.
   */
  public function getDispatcherType() {
    return EntityDispatcherEvents::ENTITY_PRE_SAVE;
  }
}