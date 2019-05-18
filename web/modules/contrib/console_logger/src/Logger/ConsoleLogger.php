<?php

/**
 * @file
 * Contains \Drupal\console_logger\Logger\ConsoleLogger class.
 */

namespace Drupal\console_logger\Logger;

use Drupal\console_logger\LogPrinter;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

class ConsoleLogger implements LoggerInterface {
  use RfcLoggerTrait;

  /**
   * The log message parser service.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  protected $messageParser;

  /**
   * The Log printer service.
   *
   * @var \Drupal\console_logger\LogPrinter
   */
  protected $logPrinter;

  /**
   * Constructs a new console logger service.
   *
   * @param \Drupal\Core\Logger\LogMessageParserInterface $logMessageParserInterface
   *   The log message parser interface.
   *
   * @param \Drupal\console_logger\LogPrinter $logPrinter
   *   The log printer service.
   */
  public function __construct(LogMessageParserInterface $logMessageParserInterface, LogPrinter $logPrinter) {
    $this->messageParser = $logMessageParserInterface;
    $this->logPrinter = $logPrinter;
  }

  /**
   * Logs with an arbitrary level.
   *
   * @param mixed $level
   * @param string $message
   * @param array $context
   * @return null
   */
  public function log($level, $message, array $context = array()) {
    // Populate the message placeholders and then replace them in the message.
    $message_placeholders = $this->messageParser->parseMessagePlaceholders($message, $context);
    $message = empty($message_placeholders) ? $message : strtr($message, $message_placeholders);

    if ($level <= RfcLogLevel::CRITICAL) {
      $color = array('red',  'bold');
    }
    elseif ($level <= RfcLogLevel::ERROR) {
      $color = 'red';
    }
    elseif ($level <= RfcLogLevel::WARNING) {
      $color = 'yellow';
    }
    elseif ($level <= RfcLogLevel::NOTICE) {
      $color = 'cyan';
    }
    elseif ($level <= RfcLogLevel::INFO) {
      $color = 'default';
    } else {
      $color = 'magenta';
    }

    $levels = RfcLogLevel::getLevels();
    $message = sprintf("%s (%s): %s", $levels[$level], $context['channel'], $message);


    $this->logPrinter->printToConsole($color, "\t" . $message);
  }
}
