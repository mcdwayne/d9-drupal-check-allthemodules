<?php

namespace Drupal\drd\Agent\Action\V6;

/**
 * Provides a 'ErrorLogs' code.
 */
class ErrorLogs extends Base {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $args = $this->getArguments();
    $max_length = isset($args['maxLength']) ? $args['maxLength'] : 5000;
    $log_file = ini_get('error_log');
    if (!file_exists($log_file)) {
      return array();
    }
    $offset = max(-1, (filesize($log_file) - $max_length));
    $log = file_get_contents($log_file, FILE_BINARY, NULL, $offset);
    $result['php error log'] = $log;

    return $result;
  }

}
