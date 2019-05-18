<?php

namespace Drupal\httpbl\Logger;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * (An arbitrated logger)
 *
 * HttpblLogTrapperInterface mimics LoggerInterface, adding a param that filters
 * messages according to the Httpbl config setting for log volume ($logVolume).
 * This allows verbose logging to be unconditionally added as needed, but then
 * only passed on to the actual logger when the settings conditions apply.
 *
 * The $logVolume param is NOT passed on when actual logging occurs.
 *
 * The functions are all based on log levels compliant to RFC 5424 integers:
 *
 * EMERGENCY = 0, ALERT = 1, CRITICAL = 2, ERROR = 3, WARNING = 4, NOTICE = 5,
 * INFO = 6, DEBUG = 7.
 * @see \Drupal\Core\Logger\RfcLogLevel
 *
 * Log volume filtering scheme:
 *   HTTPBL_LOG_QUIET   = LogVolume 0 - Quiet   -  Passes levels 0 -- 3
 *   HTTPBL_LOG_MIN     = LogVolume 1 - Minimal -  Passes levels 0 -- 5
 *   HTTPBL_LOG_VERBOSE = LogVolume 2 - Verbose -  Passes levels 0 -- 7
 *
 * @ingroup httpbl_api
 */
class HttpblLogTrapper implements HttpblLogTrapperInterface {
  use RfcLoggerTrait;
  use DependencySerializationTrait;

  /**
   * The message's placeholders parser.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  protected $parser;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a HttpblLogtrapper object.
   *
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger to add.
   */
  public function __construct(LogMessageParserInterface $parser, LoggerChannelInterface $logger) {
    $this->parser = $parser;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function trapEmergency($message, array $context = array(), $logVolume = HTTPBL_LOG_QUIET) {
    // Only pass message if logVolume meets or exceeds the minimum to pass on.
    if (\Drupal::state()->get('httpbl.log') >= $logVolume) {
      $this->log(RfcLogLevel::EMERGENCY, $message, $context);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trapAlert($message, array $context = array(), $logVolume = HTTPBL_LOG_QUIET) {
    // Only pass message if logVolume meets or exceeds the minimum to pass on.
    if (\Drupal::state()->get('httpbl.log') >= $logVolume) {
      $this->log(RfcLogLevel::ALERT, $message, $context);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trapCritical($message, array $context = array(), $logVolume = HTTPBL_LOG_QUIET) {
    // Only pass message if logVolume meets or exceeds the minimum to pass on.
    if (\Drupal::state()->get('httpbl.log') >= $logVolume) {
      $this->log(RfcLogLevel::CRITICAL, $message, $context);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trapError($message, array $context = array(), $logVolume = HTTPBL_LOG_QUIET) {
    // Only pass message if logVolume meets or exceeds the minimum to pass on.
    if (\Drupal::state()->get('httpbl.log') >= $logVolume) {
      $this->log(RfcLogLevel::ERROR, $message, $context);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trapWarning($message, array $context = array(), $logVolume = HTTPBL_LOG_MIN) {
    // Only pass message if logVolume meets or exceeds the minimum to pass on.
    if (\Drupal::state()->get('httpbl.log') >= $logVolume) {
      $this->log(RfcLogLevel::WARNING, $message, $context);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trapNotice($message, array $context = array(), $logVolume = HTTPBL_LOG_MIN) {
    // Only pass message if logVolume meets or exceeds the minimum to pass on.
    if (\Drupal::state()->get('httpbl.log') >= $logVolume) {
      $this->log(RfcLogLevel::NOTICE, $message, $context);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trapInfo($message, array $context = array(), $logVolume = HTTPBL_LOG_VERBOSE) {
    // Only pass message if logVolume meets or exceeds the minimum to pass on.
    if (\Drupal::state()->get('httpbl.log') >= $logVolume) {
      $this->log(RfcLogLevel::INFO, $message, $context);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trapDebug($message, array $context = array(), $logVolume = HTTPBL_LOG_VERBOSE) {
    // Only pass message if logVolume meets or exceeds the minimum to pass on.
    if (\Drupal::state()->get('httpbl.log') >= $logVolume) {
      $this->log(RfcLogLevel::DEBUG, $message, $context);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = array()) {
    // Convert PSR3-style messages to SafeMarkup::format() style, so they can be
    // translated too in runtime.
    // @todo No longer using this for the time being.  Remove once certain it is
    // not needed.  Removal includes removing the parsing service from the
    // service container.
    $message_placeholders = $this->parser->parseMessagePlaceholders($message, $context);
    
      switch ($level) {
        case 7:
          $method = 'debug';
          break;
        case 6:
          $method = 'info';
          break;
        case 5:
          $method = 'notice';
          break;
        case 4:
          $method = 'warning';
          break;
        case 3:
          $method = 'error';
          break;
        case 2:
          $method = 'critical';
          break;
        case 1:
          $method = 'alert';
          break;
        case 0:
          $method = 'emergency';
          break;
      }
    
    $this->logger->$method($message, $context);
   }

}