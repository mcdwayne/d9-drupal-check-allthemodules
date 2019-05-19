<?php

namespace Drupal\sitelog\Query\SystemEvents;

class logsQuery {
  public static function query($connection, $start, $end) {
    $query = $connection->select('watchdog', 'w')
      ->fields('w', array('severity'))
      ->condition('timestamp', array($start, $end), 'BETWEEN');
    $query->addExpression('count(severity)');
    $query->groupBy("severity");
    return $query->execute()->fetchAllKeyed();
  }
}
