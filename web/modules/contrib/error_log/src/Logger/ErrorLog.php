<?php

namespace Drupal\error_log\Logger;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

/**
 * Logs events to the PHP error log.
 */
class ErrorLog implements LoggerInterface {

  use RfcLoggerTrait;

  /**
   * Provides untranslated log levels.
   */
  const LOG_LEVELS = [
    RfcLogLevel::EMERGENCY => 'emergency',
    RfcLogLevel::ALERT => 'alert',
    RfcLogLevel::CRITICAL => 'critical',
    RfcLogLevel::ERROR => 'error',
    RfcLogLevel::WARNING => 'warning',
    RfcLogLevel::NOTICE => 'notice',
    RfcLogLevel::INFO => 'info',
    RfcLogLevel::DEBUG => 'debug',
  ];

  /**
   * A configuration object containing syslog settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The message's placeholders parser.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  protected $parser;

  /**
   * Constructs an Error Log object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory object.
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LogMessageParserInterface $parser) {
    $this->config = $config_factory->get('error_log.settings');
    $this->parser = $parser;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    if (empty($this->config->get('log_levels')["level_$level"])) {
      return;
    }
    if (in_array($context['channel'], $this->config->get('ignored_channels') ?: [])) {
      return;
    }
    // Drush handles error logging for us, so disable redundant logging.
    if (function_exists('drush_main') && !ini_get('error_log')) {
      return;
    }
    $level = static::LOG_LEVELS[$level];
    $message_placeholders = $this->parser->parseMessagePlaceholders($message, $context);
    $message = empty($message_placeholders) ? $message : strtr($message, $message_placeholders);
    $message = "[$level] [{$context['channel']}] [{$context['ip']}] [uid:{$context['uid']}] [{$context['request_uri']}] [{$context['referer']}] $message";
    return error_log($message);
  }

}
