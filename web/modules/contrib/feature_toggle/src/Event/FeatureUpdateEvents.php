<?php

namespace Drupal\feature_toggle\Event;

/**
 * Defines events for the migration system.
 *
 * @see \Drupal\feature_toggle\Event\FeatureUpdateEvent
 */
final class FeatureUpdateEvents {

  /**
   * Name of event fired when features are updated.
   *
   * @Event
   *
   * @var string
   */
  const UPDATE = 'feature_toggle.update';

}
