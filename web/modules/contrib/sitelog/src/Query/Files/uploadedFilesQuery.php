<?php

namespace Drupal\sitelog\Query\Files;

class uploadedFilesQuery {
  public static function query() {
    return \Drupal::service('entity.query')
      ->get('file')
      ->condition('status', 1)
      ->count()
      ->execute();
  }
}
