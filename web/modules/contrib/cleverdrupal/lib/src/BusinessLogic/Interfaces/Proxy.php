<?php

namespace CleverReach\BusinessLogic\Interfaces;

/**
 *
 */
interface Proxy {
  const CLASS_NAME = __CLASS__;

  /**
   * Call http client.
   *
   * @param $method
   * @param $endpoint
   * @param array $body
   * @param string $accessToken
   *
   * @return array
   */
  public function call($method, $endpoint, $body = [], $accessToken = '');

}
