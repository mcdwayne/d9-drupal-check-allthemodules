<?php

namespace Drupal\new_relic_rpm\Logger;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\new_relic_rpm\ExtensionAdapter\NewRelicAdapterInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * A Logger that allows sending messages to the New Relic API.
 */
class NewRelicLogger implements LoggerInterface {

  use LoggerTrait;

  /**
   * The message's placeholders parser.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  protected $parser;

  /**
   * The Adapter for the New Relic extension.
   *
   * @var \Drupal\new_relic_rpm\ExtensionAdapter\NewRelicAdapterInterface
   */
  protected $adapter;

  /**
   * Constructs a DbLog object.
   *
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   * @param \Drupal\new_relic_rpm\ExtensionAdapter\NewRelicAdapterInterface $adapter
   *   The new relic adapter.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory used to read new relic settings.
   */
  public function __construct(LogMessageParserInterface $parser, NewRelicAdapterInterface $adapter, ConfigFactoryInterface $configFactory) {
    $this->parser = $parser;
    $this->adapter = $adapter;
    $this->config = $configFactory;
  }

  /**
   * Check whether we should log the message or not based on the level.
   *
   * @param int $level
   *   The RFC 5424 log level.
   *
   * @return bool
   *   Indicator of whether the message should be logged or not.
   */
  private function shouldLog($level) {
    $validLevels = $this->config->get('new_relic_rpm.settings')->get('watchdog_severities');
    return in_array($level, $validLevels);
  }

  /**
   * Get a human readable severity name for an RFC log level.
   *
   * @param int $level
   *   The RFC 5424 log level.
   *
   * @return string
   *   The human readable severity name.
   */
  private function getSeverityName($level) {
    $levels = RfcLogLevel::getLevels();
    if (isset($levels[$level])) {
      return $levels[$level]->getUntranslatedString();
    }
    return 'Unknown';
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    $message_placeholders = $this->parser->parseMessagePlaceholders($message, $context);

    // Skip if already logged.
    // @todo
    if (!empty($context['variables']['new_relic_already_logged'])) {
      return;
    }

    // Check if the severity is supposed to be logged.
    if (!$this->shouldLog($level)) {
      return;
    }

    $format = "@message | Severity: (@severity) @severity_desc | Type: @type | Request URI: @request_uri | Referrer URI: @referer_uri | User: @uid | IP Address: @ip";

    $message = strtr($format, [
      '@severity' => $level,
      '@severity_desc' => $this->getSeverityName($level),
      '@type' => $context['channel'],
      '@ip' => $context['ip'],
      '@request_uri' => $context['request_uri'],
      '@referer_uri' => $context['referer'],
      '@uid' => $context['uid'],
      '@message' => strip_tags(strtr($message, $message_placeholders)),
    ]);

    $this->adapter->logError($message);
  }

}
