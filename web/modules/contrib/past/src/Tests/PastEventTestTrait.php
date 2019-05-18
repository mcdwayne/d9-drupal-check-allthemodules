<?php

namespace Drupal\past\Tests;

/**
 * Provides common helper methods for Past Event tests.
 */
trait PastEventTestTrait {

  /**
   * Returns the last event with a given machine name.
   *
   * @param string $machine_name
   *   Machine name of a past event entity.
   *
   * @return \Drupal\past\PastEventInterface|null
   *   The loaded past event object or null if not found.
   */
  public function getLastEventByMachineName($machine_name) {
    $event_ids = \Drupal::entityQuery('past_event')
      ->condition('machine_name', $machine_name, '=')
      ->sort('event_id', 'DESC')
      ->range(0, 1)
      ->execute();
    if ($event_ids) {
      return \Drupal::entityTypeManager()->getStorage('past_event')->load(reset($event_ids));
    }
    return NULL;
  }

}
