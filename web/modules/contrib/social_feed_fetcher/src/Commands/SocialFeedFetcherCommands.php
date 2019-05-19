<?php

namespace Drupal\social_feed_fetcher\Commands;

use Drupal\Core\Queue\SuspendQueueException;
use Drush\Commands\DrushCommands;

/**
 * Class SocialFeedFetcherCommands.
 *
 * @package Drupal\social_feed_fetcher\Commands
 */
class SocialFeedFetcherCommands extends DrushCommands {

  /**
   * Echos back hello with the argument provided.
   *
   * @command social_feed_fetcher:import
   * @aliases sff-import
   * @usage social_feed_fetcher:import
   */
  public function import() {
    \Drupal::service('import_social_feed_service')->import();
    $queuesID = [
      'social_posts_twitter_queue_worker',
      'social_posts_linkedin_queue_worker',
      'social_posts_instagram_queue_worker',
      'social_posts_facebook_queue_worker',
    ];
    $this->runQueues($queuesID);
  }

  /**
   * @param array $queuesID
   */
  public function runQueues($queuesID = []) {
    $queue_factory = \Drupal::service('queue');
    $queue_manager = \Drupal::service('plugin.manager.queue_worker');

    foreach ($queuesID as $queueID) {
      /** @var \Drupal\Core\Queue\QueueInterface $queue */
      $queue = $queue_factory->get($queueID);
      /** @var \Drupal\Core\Queue\QueueWorkerInterface $queue_worker */
      $queue_worker = $queue_manager->createInstance($queueID);

      while ($item = $queue->claimItem()) {
        try {
          $queue_worker->processItem($item->data);
          $queue->deleteItem($item);
        } catch (SuspendQueueException $e) {
          $queue->releaseItem($item);
          break;
        } catch (\Exception $e) {
          watchdog_exception('npq', $e);
        }
      }
    }
  }

}