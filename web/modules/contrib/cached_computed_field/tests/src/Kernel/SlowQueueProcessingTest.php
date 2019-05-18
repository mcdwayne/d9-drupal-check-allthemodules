<?php

namespace Drupal\Tests\cached_computed_field\Kernel;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Tests that the entire queue gets processed, even if processing is very slow.
 *
 * @group cached_computed_field
 */
class SlowQueueProcessingTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create 3 test entities.
    $this->createTestEntities(3);
  }

  /**
   * Tests that all items are processed even if the processing is very slow.
   */
  public function testSlowQueueProcessing() {
    // Set up configuration to process one item at a time, and stop processing
    // after 1 second. This will ensure we can keep the test run through time to
    // a minimum.
    $this->setBatchSize(1);
    $this->setTimeLimit(1);

    // In the beginning, there should be no items in the queue.
    $this->assertEquals(0, $this->queue->numberOfItems());

    // Also, none of the items should have been processed.
    $this->assertEquals(0, $this->numberOfProcessedItems());

    // After the hook_cron() implementation was run, there should be 3 items in
    // the queue.
    $this->simulateCronRun();
    $this->assertEquals(3, $this->queue->numberOfItems());

    // Simulate a very slow API call that only is able to process 1 single item
    // on every cron run. Still, we should expect that since we have 3 items,
    // all items should be successfully processed after 3 completed cron runs.
    \Drupal::state()->set('cached_computed_field_test.processing_time', 1000000);
    for ($i = 1; $i < 4; $i++) {
      $this->simulateCronRun();
      $this->assertEquals(3 - $i, $this->queue->numberOfItems());
      $this->assertEquals($i, $this->numberOfProcessedItems());
    }
  }

  /**
   * Counts the number of processed items.
   *
   * Processed items can be recognized because their value has been set to 100.
   *
   * @see \Drupal\cached_computed_field_test\EventSubscriber\RefreshExpiredIntegerFieldSubscriber::refreshExpiredField()
   */
  protected function numberOfProcessedItems() {
    return array_reduce($this->entities, function ($count, ContentEntityInterface $entity) {
      // Reload the entity from the database to check if it has changed.
      $entity = $this->entityStorage->loadUnchanged($entity->id());
      $field_name = self::TEST_FIELD;
      // When the field value is 100 the item has been processed.
      return $count + (int) ($entity->$field_name->value == 100);
    }, 0);
  }

}
