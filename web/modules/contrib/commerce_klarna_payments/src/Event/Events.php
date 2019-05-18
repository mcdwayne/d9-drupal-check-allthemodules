<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments\Event;

/**
 * Available events.
 */
final class Events {

  /**
   * An event to alter values before creating a new session.
   *
   * @var string
   */
  public const SESSION_CREATE = 'commerce_klarna_payments.session_create';

  /**
   * An event to alter values before creating a new order.
   *
   * @var string
   */
  public const ORDER_CREATE = 'commerce_klarna_payments.order_create';

  /**
   * An event to alter values before creating a payment capture.
   *
   * @var string
   */
  public const CAPTURE_CREATE = 'commerce_klarna_payments.capture_create';

}
