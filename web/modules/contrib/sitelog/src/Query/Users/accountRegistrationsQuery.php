<?php

namespace Drupal\sitelog\Query\Users;

class accountRegistrationsQuery {
  public static function query($connection, $start, $end) {
    return $connection
      ->select('users_field_data', 'u')
      ->condition('created', array($start, $end), 'BETWEEN')
      ->countQuery()
      ->execute()
      ->fetchField();
  }
}
