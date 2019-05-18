<?php

namespace Drupal\Tests\config_log\Unit;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * Logs both messages and context variables for unit testing.
 */
class ContextLogger implements LoggerInterface {
  use LoggerTrait;

  /**
   * Array of logs, keyed by level, with each entry containing a 'message' and a
   * 'context' variable.
   *
   * @var array
   */
  protected $logs;

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = array()) {
    $this->logs[$level][] = [
      'message' => $message,
      'context' => $context,
    ];
  }

  /**
   * Return all saved log entries.
   *
   * @param bool $level
   *   (optional) Return only logs for a specified level.
   *
   * @return array
   *   An array of log entries.
   */
  public function getLogs($level = FALSE) {
    return FALSE === $level ? $this->logs : $this->logs[$level];
  }
}
