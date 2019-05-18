<?php

namespace Drupal\Tests\cached_computed_field\Kernel;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Tests the options for processing multiple items at once.
 *
 * @group cached_computed_field
 */
class BatchProcessingTest extends KernelTestBase {

  /**
   * Tests the options for processing multiple items at once.
   *
   * @dataProvider batchProcessingTestProvider
   */
  public function testBatchProcessing($items_to_process, $batch_size, $time_limit, $processing_time, $expected_results) {
    // Create test entities.
    $this->createTestEntities($items_to_process);

    $this->setBatchSize($batch_size);
    $this->setTimeLimit($time_limit);

    // Communicate the processing time to the test implementation.
    \Drupal::state()->set('cached_computed_field_test.processing_time', $processing_time);

    // In the beginning, there should be no items in the queue, and no items
    // should have been processed.
    $this->assertEquals(0, $this->queue->numberOfItems());
    $this->assertEquals(0, $this->numberOfProcessedItems());

    // All items should be queued after the first cron run.
    $this->simulateCronRun();
    $this->assertEquals($items_to_process, $this->queue->numberOfItems());

    // For every expected result set, execute the cron run and check that the
    // expected number of items have been processed.
    $total_to_be_processed = $items_to_process;
    $total_processed = 0;
    foreach ($expected_results as $expected_processed_items) {
      // Reset the number of processed items. This will be updated by the test
      // subscriber.
      \Drupal::state()->delete('cached_computed_field_test.processed_items');

      $this->simulateCronRun();

      // Retrieve the batches that were processed, as reported by the test
      // subscriber, and verify these are correct.
      $processed_items = \Drupal::state()->get('cached_computed_field_test.processed_items', []);
      $this->assertEquals($expected_processed_items, $processed_items);

      // Check that the number of items that remain in the queue, and the total
      // number of processed items are correct.
      $expected_count = array_sum($expected_processed_items);
      $this->assertEquals($total_to_be_processed -= $expected_count, $this->queue->numberOfItems());
      $this->assertEquals($total_processed += $expected_count, $this->numberOfProcessedItems());
    }

    // Finally, check that no items are left in the queue, and the full count of
    // items have been processed.
    $this->assertEquals(0, $this->queue->numberOfItems());
    $this->assertEquals($items_to_process, $this->numberOfProcessedItems());
  }

  /**
   * Data provider for ::testBatchProcessing().
   *
   * @return array
   *   An array of test cases, each test case an indexed array with the
   *   following items:
   *   - The number of items to process.
   *   - The batch size, i.e. the number of items that are processed at once.
   *   - The time limit after which no new batches are to be processed, in
   *     seconds.
   *   - The processing time per item, in microseconds.
   *   - An array containing the expected result of the test.
   *
   * @see \Drupal\Tests\cached_computed_field\Kernel\BatchProcessingTest::testBatchProcessing()
   */
  public function batchProcessingTestProvider() {
    return [
      [
        // The number of items to process.
        19,
        // The batch size.
        5,
        // The time limit.
        5,
        // The time spent processing each individual batch. A slow 2.5 second
        // time is chosen so there will be 2 batches within the 5 second time
        // limit. This gives a grace time of 2.5 seconds for the test machine to
        // execute the test code. Hopefully this will be enough to avoid too
        // many random failures.
        2500000,
        // The expected result.
        [
          // The first cron run, lasting 5 seconds, has processed 2 batches of 5
          // items.
          [5, 5],
          // The second cron run, lasting 5 seconds, has processed the remaining
          // items in 2 batches.
          [5, 4],
        ],
        [
          // The number of items to process.
          250,
          // The batch size.
          100,
          // The time limit.
          5,
          // The time spent processing each individual batch.
          0,
          // The expected result.
          [
            // The first cron run has processed all items in 3 batches.
            [100, 100, 50],
          ],
        ],
      ],
    ];
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
