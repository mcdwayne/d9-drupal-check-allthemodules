<?php

declare(strict_types = 1);

namespace Drupal\commerce_paytrail\Event;

/**
 * Class PaytrailEvents.
 *
 * @package Drupal\commerce_paytrail\Events
 */
final class PaytrailEvents {

  /**
   * Event to alter transaction repository values.
   */
  public const FORM_ALTER = 'paytrail.form_alter';

  /**
   * Event to respond when IPN creates a new payment.
   */
  public const IPN_CREATED_PAYMENT = 'paytrail.ipn_created_payment';

}
