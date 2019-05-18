<?php

namespace Drupal\acquia_contenthub_subscriber\EventSubscriber\HandleWebhook;

use Drupal\acquia_contenthub\EventSubscriber\HandleWebhook\PurgeBase;

/**
 * Class PurgeSubscriber.
 *
 * Reacts on "purge-successful" webhook and purges the subscriber's import
 * queue.
 *
 * @package Drupal\acquia_contenthub_subscriber\EventSubscriber\HandleWebhook
 */
class Purge extends PurgeBase {

  /**
   * {@inheritDoc}
   */
  protected function getQueueName(): string {
    return 'acquia_contenthub_subscriber_import';
  }

}
