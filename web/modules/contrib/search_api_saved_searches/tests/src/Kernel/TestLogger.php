<?php

namespace Drupal\Tests\search_api_saved_searches\Kernel;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\Core\Logger\RfcLogLevel;
use Psr\Log\LoggerInterface;

/**
 * Provides a logger that throws exceptions when logging errors.
 */
class TestLogger extends LoggerChannel implements LoggerChannelFactoryInterface {

  use RfcLoggerTrait;

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    if ($level < RfcLogLevel::INFO) {
      $message = strtr($message, $context);
      throw new \Exception($message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($channel) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addLogger(LoggerInterface $logger, $priority = 0) {
  }

}
