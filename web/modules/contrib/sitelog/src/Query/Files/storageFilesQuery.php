<?php

namespace Drupal\sitelog\Query\Files;

class storageFilesQuery {
  public static function query($connection) {
    $query = $connection->select('file_managed', 'f');
    $query->condition('status', 1);
    $query->addExpression('sum(filesize)');
    return $query->execute()->fetchField();
  }
}
