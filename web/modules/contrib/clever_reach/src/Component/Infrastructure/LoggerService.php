<?php

namespace Drupal\clever_reach\Component\Infrastructure;

use CleverReach\Infrastructure\Interfaces\LoggerAdapter;
use CleverReach\Infrastructure\Logger\Configuration;
use CleverReach\Infrastructure\Logger\Logger;
use Drupal;

/**
 * Logger service implementation.
 *
 * @see \CleverReach\Infrastructure\Interfaces\LoggerAdapter
 */
class LoggerService implements LoggerAdapter {

  /**
   * {@inheritdoc}
   */
  public function logMessage($data) {
    /** @var \CleverReach\Infrastructure\Logger\Configuration $configService */
    $configService = Configuration::getInstance();
    $minLogLevel = $configService->getMinLogLevel();
    $logLevel = $data->getLogLevel();

    // Min log level is actually max log level.
    if ($logLevel > $minLogLevel) {
      return;
    }

    $level = 'info';
    switch ($logLevel) {
      case Logger::ERROR:
        $level = 'error';
        break;

      case Logger::WARNING:
        $level = 'warning';
        break;

      case Logger::DEBUG:
        $level = 'debug';
        break;
    }

    Drupal::logger(ConfigService::MODULE_NAME)->log(
        $level,
        "[$level][{$data->getTimestamp()}][{$data->getComponent()}][{$data->getUserAccount()}]" .
        (string) $data->getMessage()
    );
  }

}
