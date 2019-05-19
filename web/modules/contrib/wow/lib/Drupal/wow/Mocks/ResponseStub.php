<?php

/**
 * @file
 * Stubs a Response object.
 */

namespace Drupal\wow\Mocks;

use WoW\Core\Response;

/**
 * Response Stub.
 */
class ResponseStub extends Response {

  public function __construct($code = 1, $data = '{"status":"ok", "reason":"Stub."}') {
    parent::__construct((object) array(
      'code' => $code,
      'protocol' => '',
      'request' => '',
      'headers' => array(),
      'data' => $data,
    ));
  }
}
