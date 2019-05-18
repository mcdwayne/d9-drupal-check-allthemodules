<?php

namespace Drupal\commerce_braintree_marketplace\Event;

use Braintree\WebhookNotification;

final class BraintreeMarketplaceEvents {

  /**
   * Prefix for event strings.
   */
  const PREFIX = 'commerce_braintree_marketplace';

  /**
   * Event type for payment creation.
   */
  const PAYMENT = self::PREFIX . '.payment';

  /**
   * Sub-merchant account approved event.
   */
  const SUB_MERCHANT_ACCOUNT_APPROVED = self::PREFIX . WebhookNotification::SUB_MERCHANT_ACCOUNT_APPROVED;

  /**
   * Sub-merchant account declined event.
   */
  const SUB_MERCHANT_ACCOUNT_DECLINED = self::PREFIX . WebhookNotification::SUB_MERCHANT_ACCOUNT_DECLINED;

  /**
   * Disbursement exception event.
   */
  const DISBURSEMENT_EXCEPTION = self::PREFIX . WebhookNotification::DISBURSEMENT_EXCEPTION;

  /**
   * Disbursement event.
   */
  const DISBURSEMENT = self::PREFIX . WebhookNotification::DISBURSEMENT;

}
