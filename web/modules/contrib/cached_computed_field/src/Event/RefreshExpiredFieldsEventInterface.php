<?php

namespace Drupal\cached_computed_field\Event;

/**
 * Interface for events that fire when cached computed fields expire.
 */
interface RefreshExpiredFieldsEventInterface {

  /**
   * The event name.
   */
  const EVENT_NAME = 'cached_computed_field.refresh_expired_fields';

  /**
   * Returns the expired items.
   *
   * @return \Drupal\cached_computed_field\ExpiredItemCollection
   *   A collection of expired items.
   */
  public function getExpiredItems();

}
