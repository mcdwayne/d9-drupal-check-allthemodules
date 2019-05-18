<?php

namespace Drupal\commerce_xero;

use Drupal\Core\TypedData\ComplexDataInterface;

/**
 * A compact model for tracking strategy, data object and payment.
 */
class CommerceXeroData implements CommerceXeroDataInterface {

  /**
   * Strategy entity ID.
   *
   * @var string
   */
  protected $strategyId;

  /**
   * Commerce payment entity ID.
   *
   * @var int
   */
  protected $paymentId;

  /**
   * Typed data object.
   *
   * @var \Drupal\Core\TypedData\ComplexDataInterface
   */
  protected $data;

  /**
   * Execution state.
   *
   * @var string
   */
  protected $state;

  /**
   * The poison count.
   *
   * @var int
   */
  public $count;

  /**
   * Initialize method.
   *
   * @param string $strategyId
   *   The strategy ID.
   * @param int $paymentId
   *   The payment ID.
   * @param \Drupal\Core\TypedData\ComplexDataInterface $data
   *   The typed data object.
   * @param string $state
   *   The execution state: "process" or "send", but not "immediate".
   */
  public function __construct($strategyId, $paymentId, ComplexDataInterface $data = NULL, $state = 'process') {
    $this->strategyId = $strategyId;
    $this->paymentId = $paymentId;
    $this->data = $data;
    $this->state = $state;
    $this->count = 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getStrategyEntityId() {
    return $this->strategyId;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentEntityId() {
    return $this->paymentId;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function setData(ComplexDataInterface $data) {
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getExecutionState() {
    return $this->state;
  }

  /**
   * {@inheritdoc}
   */
  public function setExecutionState($state = 'process') {
    if (!in_array($state, ['process', 'send', 'poison'])) {
      throw new \InvalidArgumentException('Illegal value for execution state');
    }
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function incrementCount() {
    $this->count = $this->count + 1;
  }

  /**
   * {@inheritdoc}
   */
  public function exceededPoisonThreshhold() {
    return $this->count > self::POISON_THRESHHOLD;
  }

}
