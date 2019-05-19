<?php

namespace Drupal\sitelog\Query\Users;

class activeAccountsQuery {
  public static function query($connection, $end) {
    return $connection
      ->select('users_field_data', 'u')
      ->condition('created', $end, '<=')
      ->condition('status', 1)
      ->countQuery()
      ->execute()
      ->fetchField();
  }
}
