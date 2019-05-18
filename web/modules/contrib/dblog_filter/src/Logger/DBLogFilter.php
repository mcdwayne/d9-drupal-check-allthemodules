<?php

namespace Drupal\dblog_filter\Logger;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\dblog\Logger\DbLog;

/**
 * DBLogFilter Class.
 */
class DBLogFilter extends DbLog {

  use RfcLoggerTrait;

  /**
   * Constructs a DbLogFilter object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection object.
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The parser to use when extracting message variables.
   */
  public function __construct(Connection $connection, LogMessageParserInterface $parser) {
    $this->connection = $connection;
    $this->parser = $parser;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = array()) {
    $level_explode = array();
    $result = FALSE;
    // Get RFC LOg levels.
    $levels = RfcLogLevel::getLevels();
    // Get Log Filter Settings.
    $config = \Drupal::config('dblog_filter.settings');
    // Get Severity levels Configuration.
    $severity_levels = $config->get('severity_levels');
    $entities_load = $config->get('log_values');
    $values = array_map('trim', explode("\n", $entities_load));
    foreach ($levels as $key => $log_level) {
      $level_array[$key] = strtolower($log_level->getUntranslatedString());
    }
    // Check for channel name and given values in log filter settings.
    foreach ($values as $value) {
      $explode_values = explode('|', $value);
      if ($explode_values[0] == $context['channel']) {
        $level_explode = explode(',', $explode_values[1]);
      }
    }
    if ($level_explode) {
      $result = in_array($level_array[$level], $level_explode);
    }
    if (!empty($severity_levels[$level_array[$level]])) {
      $result = TRUE;
    }
    // If present the only log message.
    if ($result) {
      parent::log($level, $message, $context);
    }
  }

}
