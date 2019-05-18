<?php

namespace Drupal\query\Services;

use Drupal\query\Common\Condition;

/**
 * Interface QueryInterface
 *
 * @package Drupal\query\Services
 */
interface QueryInterface {
  /**
   * @param string|null $key
   *
   * @return Condition
   */
  public function condition($key = NULL);
}
