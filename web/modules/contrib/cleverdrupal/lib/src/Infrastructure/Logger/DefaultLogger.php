<?php

namespace CleverReach\Infrastructure\Logger;

use CleverReach\Infrastructure\Interfaces\DefaultLoggerAdapter;
use CleverReach\Infrastructure\Interfaces\Required\HttpClient;
use CleverReach\Infrastructure\ServiceRegister;

/**
 *
 */
class DefaultLogger implements DefaultLoggerAdapter {

  /**
   * Sending log data to CR API.
   *
   * @param LogData $data
   */
  public function logMessage($data) {
    /** @var \CleverReach\Infrastructure\Interfaces\Required\HttpClient $httpClient */
    $httpClient = ServiceRegister::getService(HttpClient::CLASS_NAME);
    // Waiting on CR to define API endpoint.
    $httpClient->sendHttpRequestAsync('POST', '', [], json_encode(get_object_vars($data)));
  }

}
