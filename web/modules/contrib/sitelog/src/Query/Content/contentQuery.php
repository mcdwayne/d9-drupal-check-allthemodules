<?php

namespace Drupal\sitelog\Query\Content;

class contentQuery {
  public static function query() {
    $types = \Drupal::entityTypeManager()
      ->getStorage('node_type')
      ->loadMultiple();
    foreach ($types as $type) {
      $ids[] = $type->id();
    }
    foreach ($ids as $id) {
      foreach (array(0, 1) as $status) {
        $result[$id][$status] = \Drupal::service('entity.query')
          ->get('node')
          ->condition('type', $id)
          ->condition('status', $status)
          ->count()
          ->execute();
      }
    }
    return $result;
  }
}
