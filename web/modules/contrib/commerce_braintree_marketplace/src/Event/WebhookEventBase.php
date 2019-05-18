<?php

namespace Drupal\commerce_braintree_marketplace\Event;

use Braintree\WebhookNotification;
use Symfony\Component\EventDispatcher\Event;

class WebhookEventBase extends Event {

  /**
   * The webhook notification.
   *
   * @var \Braintree\WebhookNotification
   */
  protected $webHook;

  /**
   * @return \Braintree\WebhookNotification
   */
  public function getWebhook() {
    return $this->webHook;
  }

  /**
   * MerchantDisbursementExceptionEvent constructor.
   * @param \Braintree\WebhookNotification $webhook
   */
  public function __construct(WebhookNotification $webHook) {
    $this->webHook = $webHook;
  }

}
