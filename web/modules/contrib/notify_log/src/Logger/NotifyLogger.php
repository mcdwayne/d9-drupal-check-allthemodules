<?php

namespace Drupal\notify_log\Logger;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\notify_log\DataCollector\NotifyLogDataCollector;
use Psr\Log\LoggerInterface;
use Drupal\dblog\Logger\DbLog;

/**
 * Class NotifyLogger.
 */
class NotifyLogger extends DbLog implements LoggerInterface {
  use StringTranslationTrait;

  /**
   * The log data collector.
   *
   * @var \Drupal\notify_log\DataCollector\NotifyLogDataCollector
   */
  private $dataCollector;

  /**
   * Constructs a NotifyLogger object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection object.
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   * @param \Drupal\notify_log\DataCollector\NotifyLogDataCollector $dataCollector
   *   The log data collector.
   */
  public function __construct(Connection $connection, LogMessageParserInterface $parser, NotifyLogDataCollector $dataCollector) {
    parent::__construct($connection, $parser);
    $this->dataCollector = $dataCollector;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    $this->dataCollector->addLog($context['channel']);
  }

}
