<?php

namespace Drupal\commerce_hyperpay\Event;

/**
 * Defines constants for module specific event hooks.
 */
final class HyperpayPaymentEvents {

  /**
   * Name of the event to allow amount modification before checkout preparation.
   *
   * This allows to easily modify the amount, that should be charged. This way,
   * implementations are allowed to provide early payment discounts, that do not
   * change the original order total value.
   *
   * @Event
   *
   * @see \Drupal\commerce_hyperpay\Event\AlterHyperpayAmountEvent
   */
  const ALTER_AMOUNT = 'commerce_hyperpay.alter_hyperpay_amount';
}
