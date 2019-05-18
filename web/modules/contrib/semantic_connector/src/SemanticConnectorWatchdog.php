<?php

/**
 * @file
 * The Exception Classes.
 */

namespace Drupal\semantic_connector;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * The Exception Class for the Semantic Connector.
 */
class SemanticConnectorWatchdog {

  /**
   * Puts the message into the watchdog and as drupal message if is set.
   *
   * @param string $type
   * @param string $message
   * @param array $variables
   * @param int $severity
   * @param bool $showMessage
   */
  public static function message($type, $message, $variables = array(), $severity = RfcLogLevel::ERROR, $showMessage = FALSE) {
    // watchdog('PoolParty Semantic Connector - ' . $type, $message, $variables, $severity);

    \Drupal::logger('semantic_connector')->log($severity, $type . ': ' . $message, $variables);

    if ($showMessage) {
      switch ($severity) {
        case RfcLogLevel::EMERGENCY:
        case RfcLogLevel::ALERT:
        case RfcLogLevel::CRITICAL:
        case RfcLogLevel::ERROR:
          $type = 'error';
          break;

        case RfcLogLevel::WARNING:
          $type = 'warning';
          break;

        case RfcLogLevel::NOTICE:
        case RfcLogLevel::INFO:
        case RfcLogLevel::DEBUG:
        default:
          $type = 'status';
      }
      \Drupal::messenger()->addMessage(t($message, $variables), $type);
    }
  }
}