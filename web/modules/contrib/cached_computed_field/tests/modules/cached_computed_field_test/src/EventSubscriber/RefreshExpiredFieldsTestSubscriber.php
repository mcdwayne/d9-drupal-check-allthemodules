<?php

namespace Drupal\cached_computed_field_test\EventSubscriber;

use Drupal\cached_computed_field\Event\RefreshExpiredFieldsEventInterface;
use Drupal\cached_computed_field\EventSubscriber\RefreshExpiredFieldsSubscriberBase;

/**
 * An empty implementation of the event subscriber, for testing the base class.
 */
class RefreshExpiredFieldsTestSubscriber extends RefreshExpiredFieldsSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function refreshExpiredFields(RefreshExpiredFieldsEventInterface $event) {
    // Do nothing. This is just a test implementation of the base class.
  }

}
