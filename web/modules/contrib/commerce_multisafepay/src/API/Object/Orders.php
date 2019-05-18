<?php
 /**
 * Copyright © 2018 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */
namespace Drupal\commerce_multisafepay\API\Object;

class Orders extends Core {

  public $success;
  public $data;

  public function patch($body, $endpoint = '') {
    $result = parent::patch(json_encode($body), $endpoint);
    $this->success = $result->success;
    $this->data = $result->data;
    return $result;
  }

  public function get($type = 'orders', $id, $body = array(), $query_string = false) {
    $result = parent::get($type, $id, $body, $query_string);
    $this->success = $result->success;
    $this->data = $result->data;
    return $this->data;
  }

  public function post($body, $endpoint = 'orders') {
    $result = parent::post(json_encode($body), $endpoint);
    $this->success = $result->success;
    $this->data = $result->data;
    return $this->data;
  }

  public function getPaymentLink() {
    return $this->data->payment_url;
  }

}
