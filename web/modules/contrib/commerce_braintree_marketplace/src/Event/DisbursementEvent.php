<?php

namespace Drupal\commerce_braintree_marketplace\Event;

use Braintree\WebhookNotification;

class DisbursementEvent extends WebhookEventBase {

  /**
   * Transaction IDs.
   *
   * @var string[]
   */
  protected $transactionIds = [];

  /**
   * @inheritDoc
   */
  public function __construct(WebhookNotification $webHook) {
    parent::__construct($webHook);
    $this->transactionIds = $this->webHook->disbursement->transactionIds;
  }

  /**
   * Getter for transaction IDs.
   *
   * @return \string[]
   */
  public function getTransactionIds() {
    return $this->transactionIds;
  }

}
