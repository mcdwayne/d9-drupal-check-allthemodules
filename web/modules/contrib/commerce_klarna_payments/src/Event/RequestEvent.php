<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Event;

use Drupal\commerce_klarna_payments\Klarna\Data\RequestInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event to store session data.
 */
final class RequestEvent extends Event {

  protected $order;
  protected $request;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  public function __construct(OrderInterface $order) {
    $this->order = clone $order;
  }

  /**
   * Gets the order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order.
   */
  public function getOrder() : OrderInterface {
    return $this->order;
  }

  /**
   * Gets the request.
   *
   * @return \Drupal\commerce_klarna_payments\Klarna\Data\RequestInterface|null
   *   The klarna request.
   */
  public function getRequest() : ? RequestInterface {
    return $this->request;
  }

  /**
   * Sets the data.
   *
   * @param \Drupal\commerce_klarna_payments\Klarna\Data\RequestInterface $request
   *   The request.
   *
   * @return $this
   *   The self.
   */
  public function setRequest(RequestInterface $request) : self {
    $this->request = $request;
    return $this;
  }

}
