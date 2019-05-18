<?php

namespace Drupal\helper\Tests;

use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;

/**
 * Provides assertions for running queues.
 */
trait AssertQueueTrait {

  // Queues using the DatabaseQueue return a string for the size because it
  // does a database query.
  protected function getQueueSize($queue_name) {
    return (int) $queue = \Drupal::queue($queue_name)->numberOfItems();
  }

  /**
   * Assert a queue size.
   *
   * @param string $queue_name
   *   The queue name.
   * @param int $expected_size
   *   The expected size of the queue.
   *
   * @return int
   *   The actual size of the queue.
   */
  protected function assertQueueSize($queue_name, $expected_size) {
    $this->assertSame($expected_size, $this->getQueueSize($queue_name));
  }

  /**
   * Assert running a queue.
   *
   * @param string $queue_name
   *   The queue name.
   * @param int $assert_count_before
   *   The number of items to assert are in the queue before running. Use NULL
   *   to skip this assertion.
   * @param int $assert_count_after
   *   The number of items to assert are in the queue after running. Use NULL
   *   to skip this assertion. Defaults to zero items (an empty queue).
   * @param int $time_limit
   *   The number of seconds to limit running the queue. Defaults to 5 minutes.
   */
  protected function assertQueueRun($queue_name, $assert_count_before = NULL, $assert_count_after = 0, $time_limit = 300) {
    $queue = \Drupal::queue($queue_name);

    if (isset($assert_count_before)) {
      $this->assertQueueSize($queue_name, $assert_count_before);
    }

    /** @var \Drupal\Core\Queue\QueueWorkerInterface $queue_worker */
    $queue_worker = \Drupal::service('plugin.manager.queue_worker')->createInstance($queue_name);
    $end = time() + $time_limit;
    $lease_time = 1;
    while (time() < $end && ($item = $queue->claimItem($lease_time))) {
      try {
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);
      }
      catch (RequeueException $e) {
        $queue->releaseItem($item);
      }
      catch (SuspendQueueException $e) {
        $queue->releaseItem($item);
        $this->markTestSkipped($e->getMessage());
        break;
      }
    }

    if (isset($assert_count_after)) {
      if ($this->assertQueueSize($queue_name, $assert_count_after) != $assert_count_after) {
        // If the count does not match, then assume something failed and mark
        // the test skipped.
        $this->markTestSkipped("Could not complete running the {$queue_name} queue.");
      }
    }
  }

}
