<?php

namespace Drupal\query\Services;

use Drupal\query\Common\Condition;

/**
 * Class QueryService
 *
 * @package Drupal\query\Services
 */
class QueryService implements QueryInterface {
  /**
   * @inheritdoc
   */
  public function condition($key = NULL) {
    return Condition::create($key);
  }
}
