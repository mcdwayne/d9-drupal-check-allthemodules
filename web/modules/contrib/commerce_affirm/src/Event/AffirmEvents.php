<?php

namespace Drupal\commerce_affirm\Event;

/**
 * Defines events for the commerce_affirm module.
 */
final class AffirmEvents {

  /**
   * Name of the event fired before sending the checkout object to Affirm.
   *
   * @Event
   *
   * @see \Drupal\commerce_affirm\Event\AffirmTransactionDataPreSend
   */
  const AFFIRM_TRANSACTION_DATA_PRESEND = 'commerce_affirm.transaction_data_presend';

}
