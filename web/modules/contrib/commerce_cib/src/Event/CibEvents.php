<?php

namespace Drupal\commerce_cib\Event;

final class CibEvents {

  /**
   * Name of the event fired before CIB starts to create the request.
   *
   * @Event
   *
   * @see \Drupal\cib\Event\PreQuery10
   */
  const PRE_QUERY_10 = 'commerce_cib.pre_query_10';

  /**
   * Name of the event fired after the transaction initialization fails.
   *
   * @Event
   *
   * @see \Drupal\cib\Event\FailedInitialization
   */
  const FAILED_INITIALIZATION = 'commerce_cib.failed_initialization';

  /**
   * Name of the event fired when there's no communication with CIB.
   *
   * @Event
   *
   * @see \Drupal\cib\Event\NoCommunication
   */
  const NO_COMMUNICATION = 'commerce_cib.no_communication';

  /**
   * Name of the event fired when transaction fails on the CIB side.
   *
   * @Event
   *
   * @see \Drupal\cib\Event\FailedPayment
   */
  const FAILED_PAYMENT = 'commerce_cib.failed_payment';

  /**
   * Name of the event fired when a transaction timeouts.
   *
   * @Event
   *
   * @see \Drupal\cib\Event\Timeout
   */
  const TIMEOUT = 'commerce_cib.timeout';

}
