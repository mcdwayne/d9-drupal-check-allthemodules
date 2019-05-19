<?php

namespace Drupal\sitelog\Query\Searches;

class searchesQuery {
  public static function query($connection, $start, $end) {
    $query = $connection->select('watchdog', 'w');
    $query->addExpression('count(wid)', 'count');
    $query->fields('w', array('message', 'variables'));
    $query->condition('type', 'search');
    $query->condition('timestamp', array($start, $end), 'BETWEEN');
    $query->groupBy('message');
    $query->groupBy('variables');
    $query->orderBy('count', 'DESC');
    return $query->execute();
  }
}
