<?php

namespace Drupal\file_ownage\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 *
 */
class DefaultLogger implements LoggerInterface {

  use LoggerTrait;

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []) {
    /**
     * @FIXME
     * Port your hook_watchdog() logic here.
     */
  }

}
