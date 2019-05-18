<?php

/**
 * AcsfMessageUnitTestFailureException.
 */
class AcsfMessageUnitTestFailureException extends \Drupal\acsf\AcsfMessage {

  /**
   * Implements AcsfMessage::sendMessage().
   */
  protected function sendMessage($url, $method, $endpoint, array $parameters, $username, $password) {
    throw new \Drupal\acsf\AcsfMessageFailureException('error message');
  }

}
