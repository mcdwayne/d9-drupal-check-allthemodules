<?php

namespace Drupal\dblog_conditions\Logger;

use Drupal\dblog\Logger;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Psr\Log\LoggerInterface;

/**
 * Logs events in the watchdog database table.
 */
class DbLogConditions extends Logger\DbLog implements LoggerInterface {
  use RfcLoggerTrait;

  /**
   * The config object for the dblog_connections settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a DbLogConditions object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection object.
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(Connection $connection, LogMessageParserInterface $parser, ConfigFactoryInterface $config_factory) {
    parent::__construct($connection, $parser);

    $this->config = $config_factory->get('dblog_conditions.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = array()) {
    // Check the channels conditions
    if (!$this->channelCheck($context)) {
      return;
    }
    // @todo: in the future, implement more conditions here, ex: error level

    return parent::log($level, $message, $context);
  }

  /**
   * Determines whether or not the event should be logged based on the
   * channel settings.
   *
   * @param array
   *   The log event context array.
   *
   * @return bool
   *   True if the event should be passed on to DbLog for logging.
   */
  private function channelCheck(array $context) {
    $toggle = $this->config->get('channels_toggle');
    $channels = explode("\r\n", Unicode::strtolower($this->config->get('channels_list')));

    // Get the channel from the event context
    $channel = "";
    if (!empty($context) && isset($context['channel'])) {
      $channel = $context['channel'];
    }

    if (empty($channel) || empty($channels)) {
      // If channel list is empty, use default values
      return ($toggle == DBLOG_CONDITIONS_DEFAULT_INCLUDE) ? TRUE : FALSE;
    }
    else {
      // Check if the event channel is in the channel list
      $satisfied = (in_array($channel, $channels));
      $satisfied = ($toggle == DBLOG_CONDITIONS_DEFAULT_INCLUDE) ? !$satisfied : $satisfied;
    }

    return $satisfied;
  }
}
