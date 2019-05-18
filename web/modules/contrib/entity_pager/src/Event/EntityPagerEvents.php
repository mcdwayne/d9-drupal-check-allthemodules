<?php

namespace Drupal\entity_pager\Event;

/**
 * Define events for the Entity Pager module.
 */
final class EntityPagerEvents {
  /**
   * Name of the event fired when analyzing an entity pager and registering
   * feedback for the user.
   *
   * Fired before the feedback is logged to Drupal.
   *
   * @Event
   *
   * @see \Drupal\entity_pager\Event\EntityPagerAnalyzeEvent
   */
  const ENTITY_PAGER_ANALYZE = 'entity_pager.analyze';
}
