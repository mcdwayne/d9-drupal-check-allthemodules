<?php

namespace Drupal\acquia_contenthub_subscriber;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManager;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Implements an Import Queue for entities.
 */
class ContentHubImportQueue {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * Subscriber Import Queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * The Queue Worker.
   *
   * @var \Drupal\Core\Queue\QueueWorkerManager
   */
  protected $queueManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(QueueFactory $queue_factory, QueueWorkerManager $queue_manager) {
    $this->queue = $queue_factory->get('acquia_contenthub_subscriber_import');
    $this->queueManager = $queue_manager;
  }

  /**
   * Obtains the number of items in the import queue.
   *
   * @return mixed
   *   The number of items in the import queue.
   */
  public function getQueueCount() {
    return $this->queue->numberOfItems();
  }

  /**
   * Handle the route to create a batch process.
   */
  public function process() {
    $batch = [
      'title' => $this->t('Process all entities to be imported'),
      'operations' => [],
      'finished' => [[$this, 'batchFinished'], []],
    ];

    // Count number of the items in this queue, create enough batch operations.
    for ($i = 0; $i < $this->getQueueCount(); $i++) {
      // Create batch operations.
      $batch['operations'][] = [[$this, 'batchProcess'], []];
    }

    // Adds the batch sets.
    batch_set($batch);
  }

  /**
   * Process the batch.
   *
   * The batch worker will run through the queued items and process them
   * according to their queue method.
   *
   * @param mixed $context
   *   The batch context.
   */
  public function batchProcess(&$context) {
    $queueWorker = $this->queueManager->createInstance('acquia_contenthub_subscriber_import');

    if ($item = $this->queue->claimItem()) {
      try {
        $queueWorker->processItem($item->data);
        $this->queue->deleteItem($item);
      }
      catch (SuspendQueueException $exception) {
        $context['errors'][] = $exception->getMessage();
        $context['success'] = FALSE;
        $this->queue->releaseItem($item);
      }
      catch (EntityStorageException $exception) {
        $context['errors'][] = $exception->getMessage();
        $context['success'] = FALSE;
        $this->queue->releaseItem($item);
      }
    }
  }

  /**
   * Batch finish callback.
   *
   * This will inspect the results of the batch and will display a message to
   * indicate how the batch process ended.
   *
   * @param bool $success
   *   The result of batch process.
   * @param array $result
   *   The result of $context.
   * @param array $operations
   *   The operations that were run.
   */
  public static function batchFinished($success, array $result, array $operations) {
    if ($success) {
      drupal_set_message(t("Processed all Content Hub entities."));
      return;
    }
    $error_operation = reset($operations);
    drupal_set_message(t('An error occurred while processing @operation with arguments : @args', [
      '@operation' => $error_operation[0],
      '@args' => print_r($error_operation[0], TRUE),
    ]));
  }

}
