<?php

namespace Drupal\commerce_advancedqueue;

use Drupal\advancedqueue\Entity\Queue;
use Drupal\advancedqueue\Job;
use Drupal\commerce_advancedqueue\Plugin\AdvancedQueue\Backend\CommerceOrderJobBackendInterface;

/**
 * Represents a job relating to an order.
 */
class CommerceOrderJob extends Job {

  /**
   * The order ID this job relates to.
   *
   * @var int
   */
  protected $orderId;

  /**
   * Whether the save should be deferred.
   *
   * A process that is acting one order at a time may defer saving until all
   * jobs have been processed, to avoid multiple saves.
   *
   * @var bool
   */
  protected $deferOrderSave = FALSE;

  /**
   * Whether the order needs saving by the processor.
   *
   * @var bool
   */
  protected $orderNeedsSave = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $definition) {
    parent::__construct($definition);

    // Track and ensure we have an order ID.
    if (isset($definition['order_id'])) {
      $this->orderId = $definition['order_id'];
    }
    elseif (isset($this->payload['order_id'])) {
      $this->orderId = $this->payload['order_id'];
      unset($this->payload['order_id']);
    }
    else {
      throw new \InvalidArgumentException('A CommerceOrderJob must have an order ID.');
    }

    $this->verifyQueue();
  }

  /**
   * {@inheritdoc}
   *
   * @param string $type
   *   The job type.
   * @param array $payload
   *   The payload.
   * @param int $order_id
   *   The order ID this job relates to. May alternatively be set in
   *   $payload['order_id'], but at least one must be provided.
   *
   * @return static
   */
  public static function create($type, array $payload, $order_id = NULL) {
    return new static([
      'type' => $type,
      'payload' => $payload,
      'state' => self::STATE_QUEUED,
      'order_id' => $order_id,
    ]);
  }

  /**
   * Get the order ID the job relates to.
   *
   * @return int
   *   The order ID.
   */
  public function getOrderId() {
    return $this->orderId;
  }

  /**
   * {@inheritdoc}
   */
  public function setQueueId($queue_id) {
    parent::setQueueId($queue_id);
    $this->verifyQueue();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $array = parent::toArray();
    $array['order_id'] = $this->orderId;
    return $array;
  }

  /**
   * Verify the queue this job is attached to is suitable.
   *
   * @throws \Exception
   *   Thrown if the queue backend does not implement
   *   CommerceOrderJobBackendInterface.
   */
  protected function verifyQueue() {
    if (!$this->queueId) {
      return;
    }

    $queue = Queue::load($this->queueId);
    if (!($queue->getBackend() instanceof CommerceOrderJobBackendInterface)) {
      throw new \Exception('Commerce Order Jobs can only be queued with backends implementing CommerceOrderJobBackendInterface.');
    }
  }

  /**
   * Whether we should be deferring saving of the order.
   *
   * @return bool
   *   Whether saving the order should be deferred.
   */
  public function deferOrderSave() {
    return $this->deferOrderSave;
  }

  /**
   * Set whether we should defer saving of the order.
   *
   * @param bool $defer_save
   *   Whether saving the order should be deferred.
   *
   * @return $this
   */
  public function setDeferOrderSave($defer_save = TRUE) {
    $this->deferOrderSave = $defer_save;
    return $this;
  }

  /**
   * Whether the processor needs to save the order.
   *
   * @return bool
   *   Whether the order needs to be saved.
   */
  public function orderNeedsSave() {
    return $this->orderNeedsSave;
  }

  /**
   * Set whether the processor needs to save the order.
   *
   * @param bool $needs_save
   *   Whether the order needs to be saved.
   *
   * @return $this
   */
  public function setOrderNeedsSave($needs_save = TRUE) {
    $this->orderNeedsSave = $needs_save;
    return $this;
  }

}
