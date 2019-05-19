<?php

namespace Drupal\sitelog\Query\Users;

class inactiveAccountsQuery {
  public static function query($connection, $end) {
    return $connection
      ->select('users_field_data', 'u')
      ->condition('created', $end, '<=')
      ->condition('status', 0)
      ->condition('uid', 0, '<>')
      ->countQuery()
      ->execute()
      ->fetchField();
  }
}
