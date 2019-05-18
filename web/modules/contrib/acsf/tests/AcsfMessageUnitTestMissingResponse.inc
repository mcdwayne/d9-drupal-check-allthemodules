<?php

/**
 * AcsfMessageUnitTestMissingResponse.
 */
class AcsfMessageUnitTestMissingResponse extends \Drupal\acsf\AcsfMessage {

  /**
   * Implements AcsfMessage::sendMessage().
   */
  protected function sendMessage($url, $method, $endpoint, array $parameters, $username, $password) {
    return new AcsfMessageResponseUnitTest('unit.test.endpoint', 0, NULL);
  }

}
