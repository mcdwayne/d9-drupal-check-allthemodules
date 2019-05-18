<?php

namespace Drupal\Driver\Database\oracle;

use Drupal\Core\Database\Query\Truncate as QueryTruncate;

/**
 * Oracle implementation of \Drupal\Core\Database\Query\Truncate.
 */
class Truncate extends QueryTruncate {

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'TRUNCATE TABLE {' . $this->connection->escapeTable($this->table) . '} ';
  }

}
