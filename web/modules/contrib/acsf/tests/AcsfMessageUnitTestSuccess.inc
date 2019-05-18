<?php

/**
 * AcsfMessageUnitTestSuccess.
 */
class AcsfMessageUnitTestSuccess extends \Drupal\acsf\AcsfMessage {

  /**
   * Implements AcsfMessage::sendMessage().
   */
  protected function sendMessage($url, $method, $endpoint, array $parameters, $username, $password) {
    $response = json_encode([
      'url' => $url,
      'method' => $method,
      'endpoint' => $endpoint,
      'parameters' => $parameters,
      'username' => $username,
      'password' => $password,
    ]);
    return new AcsfMessageResponseUnitTest($endpoint, 0, $response);
  }

}
