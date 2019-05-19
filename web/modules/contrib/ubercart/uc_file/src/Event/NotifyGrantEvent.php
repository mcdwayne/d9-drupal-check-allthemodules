<?php

namespace Drupal\uc_file\Event;

use Drupal\uc_order\OrderInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a file download is granted.
 */
class NotifyGrantEvent extends Event {

  const EVENT_NAME = 'uc_file_notify_grant';

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
