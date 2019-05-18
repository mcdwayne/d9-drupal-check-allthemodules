<?php
 /**
 * Copyright © 2018 MultiSafepay, Inc. All rights reserved.
 * See DISCLAIMER.md for disclaimer details.
 */
namespace Drupal\commerce_multisafepay\API\Object;

class Gateways extends Core {

  public $success;
  public $data;

  public function get($endpoint = 'gateways', $type = '', $body = array(), $query_string = false) {
    $result = parent::get($endpoint, $type, json_encode($body), $query_string);
    $this->success = $result->success;
    $this->data = $result->data;

    return $this->data;
  }

}
