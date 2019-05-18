<?php

namespace Drupal\messagebird;

/**
 * Interface MessageBirdExceptionInterface.
 *
 * @package Drupal\messagebird
 */
interface MessageBirdExceptionInterface {

  /**
   * Log the right status for the given Exception type.
   *
   * @param \Throwable $exception
   *   Exception Object.
   * @param array $args
   *    (optional) Arguments for t().
   */
  public function logError(\Throwable $exception, array $args = array());

}
