<?php

namespace CleverReach\Infrastructure\Interfaces\Required;

use CleverReach\Infrastructure\Logger\Logger;
use CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException;

/**
 *
 */
abstract class HttpClient {
  const CLASS_NAME = __CLASS__;

  /**
   * Create, log and send request.
   *
   * @param string $method
   * @param string $url
   * @param array $headers
   * @param string $body
   *
   * @return \CleverReach\Infrastructure\Utility\HttpResponse
   */
  public function request($method, $url, $headers = [], $body = '') {
    Logger::logDebug(json_encode([
      'Type' => $method,
      'Endpoint' => $url,
      'Headers' => json_encode($headers),
      'Content' => $body,
    ]));

    /** @var \CleverReach\Infrastructure\Utility\HttpResponse $response */
    $response = $this->sendHttpRequest($method, $url, $headers, $body);

    Logger::logDebug(json_encode([
      'ResponseFor' => "{$method} at {$url}",
      'Status' => $response->getStatus(),
      'Headers' => json_encode($response->getHeaders()),
      'Content' => $response->getBody(),
    ]));

    return $response;
  }

  /**
   * Create, log and send request asynchronously.
   *
   * @param string $method
   * @param string $url
   * @param array $headers
   * @param string $body
   */
  public function requestAsync($method, $url, $headers = [], $body = '') {
    Logger::logDebug(json_encode([
      'Type' => $method,
      'Endpoint' => $url,
      'Headers' => $headers,
      'Content' => $body,
    ]));

    $this->sendHttpRequestAsync($method, $url, $headers, $body);
  }

  /**
   * @param $method
   * @param $url
   * @param array $headers
   * @param string $body
   *
   * @return bool
   */
  public function autoConfigure($method, $url, $headers = [], $body = '') {
    $passed = $this->isRequestSuccessful($method, $url, $headers, $body);
    if ($passed) {
      return TRUE;
    }

    $combinations = $this->getAdditionalOptions();
    foreach ($combinations as $combination) {
      $this->setAdditionalOptions($combination);
      $passed = $this->isRequestSuccessful($method, $url, $headers, $body);
      if ($passed) {
        return TRUE;
      }

      $this->resetAdditionalOptions();
    }

    return FALSE;
  }

  /**
   * Get additional options for request.
   *
   * @return array
   */
  protected function getAdditionalOptions() {
    // Left blank intentionally so integrations can override this method,
    // in order to return all possible combinations for additional curl options.
  }

  /**
   * Save additional options for request.
   *
   * @param array OptionsDTO $options
   */
  protected function setAdditionalOptions($options) {
    // Left blank intentionally so integrations can override this method,
    // in order to save combination to some persisted array which `HttpClient` can use it later while creating request.
  }

  /**
   * Reset additional options for request to default value.
   */
  protected function resetAdditionalOptions() {
    // Left blank intentionally so integrations can override this method,
    // in order to reset to its default values persisted array which `HttpClient` uses later while creating request.
  }

  /**
   *
   */
  private function isRequestSuccessful($method, $url, $headers = [], $body = '') {
    try {
      /** @var \CleverReach\Infrastructure\Utility\HttpResponse $response */
      $response = $this->request($method, $url, $headers, $body);
    }
    catch (HttpCommunicationException $ex) {
      $response = NULL;
    }

    if (isset($response) && $response->isSuccessful()) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Create and send request.
   *
   * @param string $method
   * @param string $url
   * @param array $headers
   * @param string $body
   *   In JSON format.
   *
   * @return \CleverReach\Infrastructure\Utility\HttpResponse
   *
   * @throws HttpCommunicationException Only in situation when there is no connection, no response, throw this exception
   */
  abstract public function sendHttpRequest($method, $url, $headers = [], $body = '');

  /**
   * Create and send request asynchronously.
   *
   * @param string $method
   * @param string $url
   * @param array $headers
   * @param string $body
   *   In JSON format.
   */
  abstract public function sendHttpRequestAsync($method, $url, $headers = [], $body = '');

}
