<?php

namespace Drupal\Tests\queue_unique\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;
use Drupal\queue_unique\UniqueDatabaseQueue;

/**
 * Unique queue kernel test.
 *
 * @group queue_unique
 */
class QueueUniqueTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = ['queue_unique'];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    $this->container->setParameter('install_profile', 'testing');
  }

  /**
   * Test that queues that are not paused are not effected by this module.
   */
  public function testQueueIsUnique() {
    $queue_factory = $this->container->get('queue_unique.database');

    /* @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $queue_factory->get('queue');

    $this->assertInstanceOf(UniqueDatabaseQueue::class, $queue);

    $data = 1;

    // Add an item to the empty unique queue.
    $item_id = $queue->createItem($data);
    $this->assertNotFalse($item_id);
    $this->assertEquals(1, $queue->numberOfItems());

    // When we try to add the item again we should not get an item id as the
    // item has not been readded and the number of items on the queue should
    // stay the same.
    $duplicate_id = $queue->createItem($data);
    $this->assertFalse($duplicate_id);
    $this->assertEquals(1, $queue->numberOfItems());

    // Claim and delete the item from the queue simulating an item being
    // processed.
    $item = $queue->claimItem();
    $queue->deleteItem($item);

    // With the original item being gone we should be able to readd an item
    // with the same data.
    $item_id = $queue->createItem($item_id);
    $this->assertNotFalse($item_id);
    $this->assertEquals(1, $queue->numberOfItems());
  }

}
