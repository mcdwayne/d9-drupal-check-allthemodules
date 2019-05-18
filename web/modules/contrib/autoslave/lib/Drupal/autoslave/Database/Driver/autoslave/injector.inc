<?php

use Drupal\Core\Database\Query\Select as QuerySelect;

class AutoSlaveSelectQueryInjector extends QuerySelect {
  static function changeConnection($query, $connection) {
    $query->connection = $connection;
  }
}

