<?php

namespace Drupal\Tests\nodequeue_migrate\Kernel\Migrate\d7;

use Drupal\entityqueue\Entity\EntityQueue;
use Drupal\entityqueue\Entity\EntitySubqueue;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests nodequeue migration.
 *
 * @group nodequeue
 */
class MigrateNodeQueueTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'nodequeue_migrate',
    'entityqueue',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadFixture(__DIR__ . '/../../../../fixtures/drupal7.php');
    $this->installEntitySchema('entity_subqueue');
    $this->executeMigrations([
      'd7_nodequeue',
      'd7_nodesubqueue',
    ]);
  }

  /**
   * Asserts various aspects of a entityqueue entity.
   *
   * @param string $id
   *   The entityqueue id.
   * @param string $label
   *   The expected label.
   * @param array $target_bundles
   *   The expected target bundles.
   * @param string $handler
   *   The expected handler.
   * @param int $max_size
   *   The expected maximum size.
   */
  protected function assertQueueEntity($id, $label, $target_bundles, $handler = 'simple', $max_size = 0) {
    /** @var EntityQueue $queue */
    $queue = EntityQueue::load($id);
    $this->assertInstanceOf('Drupal\entityqueue\Entity\EntityQueue', $queue);
    $this->assertSame($label, $queue->label());
    $this->assertSame($target_bundles, $queue->getEntitySettings()['handler_settings']['target_bundles']);
    $this->assertSame($handler, $queue->getHandler());
    $this->assertSame($max_size, $queue->getMaximumSize());
  }

  /**
   * Asserts various aspects of a entitysubqueue entity.
   *
   * @param string $id
   *   The entitysubqueue id.
   * @param string $queue_id
   *   The expected entityqueue id.
   * @param string $title
   *   The expected entitysubqueue title.
   * @param array $items
   *   The expected items.
   */
  protected function assertSubqueueEntity($id, $queue_id, $title, $items) {
    /** @var EntitySubqueue $subqueue */
    $subqueue = EntitySubqueue::load($id);
    $this->assertInstanceOf('Drupal\entityqueue\Entity\EntitySubqueue', $subqueue);
    $this->assertSame($queue_id, $subqueue->getQueue()->id());
    $this->assertSame($title, $subqueue->getTitle());
    foreach ($subqueue->get('items')->getValue() as $key => $item) {
      $this->assertSame((string) $items[$key], $item['target_id']);
    }
  }

  /**
   * Test nodequeue migration from Drupal 7 to 8.
   */
  public function testNodequeue() {
    $this->assertQueueEntity('queue_example_1', 'Queue example 1', ['page', 'blog']);
    $this->assertQueueEntity('queue_parent_example', 'Queue parent example', ['page'], 'multiple', 99);

    $this->assertSubqueueEntity('queue_example_1', 'queue_example_1', 'Subqueue example 1', [5, 6]);
    $this->assertSubqueueEntity('2', 'queue_parent_example', 'Subqueue example 2', [1]);
    $this->assertSubqueueEntity('3', 'queue_parent_example', 'Subqueue example 3', [2, 3]);
  }

}
