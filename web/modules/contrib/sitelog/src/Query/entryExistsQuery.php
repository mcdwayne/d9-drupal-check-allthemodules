<?php

namespace Drupal\sitelog\Query;

class entryExistsQuery {
  public static function query($connection, $start, $table) {
    return $connection
      ->select($table, 's')
      ->fields('s')
      ->condition('logged', $start)
      ->execute()
      ->fetch();
  }
}
