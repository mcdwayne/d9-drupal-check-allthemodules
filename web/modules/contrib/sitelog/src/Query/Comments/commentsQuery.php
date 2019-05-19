<?php

namespace Drupal\sitelog\Query\Comments;

class commentsQuery {
  public static function query() {
    return \Drupal::service('entity.query')->get('comment')->count()->execute();
  }
}
