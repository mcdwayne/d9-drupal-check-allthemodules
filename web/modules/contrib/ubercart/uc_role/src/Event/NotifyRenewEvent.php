<?php

namespace Drupal\uc_role\Event;

use Drupal\uc_order\OrderInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a user role is renewed.
 */
class NotifyRenewEvent extends Event {

  const EVENT_NAME = 'uc_role_notify_renew';

  /**
   * The order.
   *
   * @var \Drupal\uc_order\OrderInterface
   */
  public $order;

  /**
   * The expiration.
   *
   * @var array
   */
  public $expiration;

  /**
   * Constructs the object.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order object.
   * @param array $expiration
   *   The expiration.
   */
  public function __construct(OrderInterface $order, array $expiration) {
    $this->order = $order;
    $this->expiration = $expiration;
  }

}
