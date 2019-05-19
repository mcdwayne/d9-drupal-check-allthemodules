<?php

namespace Drupal\warmer;

/**
 * Value object to store in the queue with all the infor to process a batch.
 */
class QueueData {

  /**
   * The callback to call on dequeue.
   *
   * @var callable
   */
  private $callback;

  /**
   * The item IDs to process.
   *
   * @var array
   */
  private $ids;

  /**
   * The warmer ID.
   *
   * @var string
   */
  private $warmerId;

  /**
   * Creates a queue data object.
   *
   * @param callable $callback
   *   The callback to call on dequeue.
   * @param array $ids
   *   The item IDs to process.
   * @param $warmer_id
   *   The warmer ID.
   */
  public function __construct(callable $callback, array $ids, $warmer_id) {
    $this->callback = $callback;
    $this->ids = $ids;
    $this->warmerId = $warmer_id;
  }


  /**
   * Function to execute after claiming the item.
   */
  public function process() {
    call_user_func($this->callback, $this->ids, $this->warmerId);
  }

}
