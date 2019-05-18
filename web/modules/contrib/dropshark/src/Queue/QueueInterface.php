<?php

namespace Drupal\dropshark\Queue;

use Drupal\dropshark\Collector\CollectorInterface;

/**
 * Interface QueueInterface.
 */
interface QueueInterface {

  /**
   * Add items to the DropShark queue.
   *
   * @param array $data
   *   Data to be added to the queue.
   */
  public function add(array $data);

  /**
   * Indicates if the queue has deferred collectors.
   *
   * @return bool
   *   Indicates if the queue has deferred collectors.
   */
  public function hasDeferred();

  /**
   * Indicates if the queue should be immediately transmitted.
   *
   * Immediate means as the end of the current HTTP request. Alternatively data
   * will be held until a collector indicates its data must be transmitted
   * immediately or some other process initiates a transmission.
   *
   * @return bool
   *   Indicates if the queue should be immediately transmitted.
   */
  public function needsImmediateTransmit();

  /**
   * Moves collected items to persistent storage.
   */
  public function persist();

  /**
   * Process deferred collectors.
   */
  public function processDeferred();

  /**
   * Queue a deferred check.
   *
   * @param \Drupal\dropshark\Collector\CollectorInterface $collector
   *   The collector to queue for deferred check.
   */
  public function setDeferred(CollectorInterface $collector);

  /**
   * Set the queue to be transmitted immediately.
   */
  public function setImmediateTransmit();

  /**
   * Process items from the queue to the DropShark backend.
   */
  public function transmit();

}
