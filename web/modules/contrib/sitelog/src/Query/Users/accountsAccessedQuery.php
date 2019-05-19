<?php

namespace Drupal\sitelog\Query\Users;

class accountsAccessedQuery {
  public static function query($connection, $start, $end) {
    return $connection
      ->select('users_field_data', 'u')
      ->condition('access', array($start, $end), 'BETWEEN')
      ->countQuery()
      ->execute()
      ->fetchField();
  }
}
