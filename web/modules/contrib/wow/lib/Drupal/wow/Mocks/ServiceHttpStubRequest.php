<?php

namespace Drupal\wow\Mocks;

use WoW\Core\Response;
use WoW\Core\Service\ServiceHttp;

/**
 * This class extends the ServiceHttp used in production and under tests.
 *
 * A stub is used here on the __request() method to prevent real requests.
 */
class ServiceHttpStubRequest extends ServiceHttp {

  protected function getHost() {
    return $this->region;
  }

  protected function __request($url, array $options) {
    return new Response((object) array(
      'code' => 200,
      'protocol' => 'HTTP/1.1',
      'request' => $url,
      'headers' => $options['headers'],
      'data' => '{"status":"ok","reason":"This is a Stub."}',
    ));
  }

}
