<?php

namespace Drupal\commerce_vantiv\Event;

final class VantivEvents {

  /**
   * Name of the event fired before a payment creation request is sent.
   *
   * @Event
   *
   * @see \Drupal\commerce_vantiv\Event\FilterVantivRequestEvent
   */
  const PAYMENT_CREATE_REQUEST = 'commerce_vantiv.payment_create_request';

  /**
   * Name of the event fired before a payment capture request is sent.
   *
   * @Event
   *
   * @see \Drupal\commerce_vantiv\Event\FilterVantivRequestEvent
   */
  const PAYMENT_CAPTURE_REQUEST = 'commerce_vantiv.payment_capture_request';

  /**
   * Name of the event fired before a payment void request is sent.
   *
   * @Event
   *
   * @see \Drupal\commerce_vantiv\Event\FilterVantivRequestEvent
   */
  const PAYMENT_VOID_REQUEST = 'commerce_vantiv.payment_void_request';

  /**
   * Name of the event fired before a payment refund request is sent.
   *
   * @Event
   *
   * @see \Drupal\commerce_vantiv\Event\FilterVantivRequestEvent
   */
  const PAYMENT_REFUND_REQUEST = 'commerce_vantiv.payment_refund_request';

}
