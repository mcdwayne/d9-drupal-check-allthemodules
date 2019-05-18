<?php

namespace Drupal\commerce_tpay\Controller;

use tpayLibs\src\_class_tpay\Notifications\BasicNotificationHandler;

class TransactionNotification extends BasicNotificationHandler {
  
  public function __construct($merchant_id, $merchant_secret) {
    $this->merchantId = $merchant_id;
    $this->merchantSecret = $merchant_secret;
    parent::__construct();
  }
}
