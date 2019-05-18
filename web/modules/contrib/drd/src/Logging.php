<?php

namespace Drupal\drd;

use Drupal\Component\Render\FormattableMarkup;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class Logging.
 *
 * @package Drupal\drd
 */
class Logging {

  protected $debug = FALSE;

  /**
   * The input-output console object for logging.
   *
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   */
  protected $io;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->debug = \Drupal::config('drd.general')->get('debug');
  }

  /**
   * Enforce debugging even of CLI option wasn't set.
   */
  public function enforceDebug() {
    $this->debug = TRUE;
  }

  /**
   * Set the input-output object for logging.
   *
   * @param \Symfony\Component\Console\Style\SymfonyStyle $io
   *   The input-output object.
   */
  public function setIo(SymfonyStyle $io) {
    $this->io = $io;
  }

  /**
   * Log and output to console a message with arguments.
   *
   * @param string $severity
   *   The message severity.
   * @param string $message
   *   The message string.
   * @param array $args
   *   Arguments for the message.
   */
  public function log($severity, $message, array $args = []) {
    if (!method_exists(\Drupal::logger('drd'), $severity)) {
      $severity = 'emergency';
    }
    $arguments = [];

    $plugin_available = isset($args['@plugin_id']);
    $entity_available = isset($args['@entity_type']);

    if ($plugin_available && $entity_available) {
      $message = '@plugin_id [@entity_type/@entity_id]: ' . $message;
    }
    elseif ($plugin_available) {
      $message = '@plugin_id: ' . $message;
    }
    elseif ($entity_available) {
      $message = '[@entity_type/@entity_id]: ' . $message;
    }

    $loggerMessage = '';
    foreach ($args as $arg => $value) {
      if ($arg == 'link' || strpos($message, $arg) !== FALSE) {
        $arguments[$arg] = $value;
      }
      else {
        if (is_scalar($value)) {
          $message .= ' ' . $arg;
          $arguments[$arg] = $value;
        }
        else {
          $loggerMessage .= ' ' . $arg;
          $arguments[$arg] = json_encode($value);
        }
      }
    }

    if (!isset($this->io)) {
      $message = $message .
        ($entity_available ? '<br>@entity_name<br>' : '') .
        $loggerMessage;
      \Drupal::logger('drd')->{$severity}($message, $arguments);
    }
    else {
      if ($this->debug) {
        $message .= $loggerMessage;
      }
      $this->io->{$this->ioCallback($severity)}(new FormattableMarkup($message, $arguments));
    }
  }

  /**
   * Debug a message but only if debug mode is turned on.
   *
   * @param string $message
   *   The debug message.
   * @param array $args
   *   The message arguments.
   */
  public function debug($message, array $args = []) {
    if (!$this->debug) {
      return;
    }
    $this->log('debug', $message, $args);
  }

  /**
   * Map severities to available values.
   *
   * @param string $severity
   *   The reveiced severity.
   *
   * @return string
   *   The mapped severity.
   */
  private function ioCallback($severity) {
    switch ($severity) {
      case 'emergency':
      case 'alert':
      case 'critical':
      case 'error':
        return 'error';

      case 'warning':
        return 'warning';

      case 'notice':
      case 'info':
      case 'debug':
      default:
        return 'note';

    }
  }

}
