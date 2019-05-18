<?php

namespace Drupal\commerce_vipps\Event;

final class VippsEvents {

  /**
   * Fired before payment is initiated against Vipps.
   *
   * @Event
   *
   * @see \Drupal\commerce_vipps\Event\InitiatePaymentOptionsEvent
   */
  const INITIATE_PAYMENT_OPTIONS = 'commerce_vipps.initiate_payment_options';

}
