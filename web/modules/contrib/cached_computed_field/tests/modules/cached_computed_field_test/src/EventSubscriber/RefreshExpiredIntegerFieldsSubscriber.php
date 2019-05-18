<?php

namespace Drupal\cached_computed_field_test\EventSubscriber;

use Drupal\cached_computed_field\Event\RefreshExpiredFieldsEventInterface;
use Drupal\cached_computed_field\EventSubscriber\RefreshExpiredFieldsSubscriberBase;
use Drupal\cached_computed_field\ExpiredItemInterface;

/**
 * A test implementation of an event subscriber that refreshes expired fields.
 */
class RefreshExpiredIntegerFieldsSubscriber extends RefreshExpiredFieldsSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function refreshExpiredFields(RefreshExpiredFieldsEventInterface $event) {
    foreach ($event->getExpiredItems() as $item) {
      $entity = $this->getEntity($item);
      $entity->set($item->getFieldName(), [
        // Set the value to '100' so we can recognize it has been processed.
        'value' => 100,
        // Don't set the expiration date so that this is immediately marked as
        // expired. We don't want to wait for time to pass in tests.
        'expire' => NULL,
      ]);
      $entity->save();
    }

    // Wait for the time that is defined in the test case, simulating slow API
    // calls.
    $sleepy_time = \Drupal::state()->get('cached_computed_field_test.processing_time');
    usleep($sleepy_time);

    // Keep track of the number of items that were processed, so we can validate
    // this in the test.
    $processed_items = \Drupal::state()->get('cached_computed_field_test.processed_items', []);
    $processed_items[] = count($event->getExpiredItems());
    \Drupal::state()->set('cached_computed_field_test.processed_items', $processed_items);

  }

  /**
   * {@inheritdoc}
   */
  public function fieldNeedsRefresh(ExpiredItemInterface $item) {
    // Avoid using a time based expiration so we don't need to wait for precious
    // seconds to pass during tests. Our test fields are always ready to be
    // refreshed.
    return TRUE;
  }

}
