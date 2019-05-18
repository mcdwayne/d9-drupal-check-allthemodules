<?php

namespace Drupal\precision_modifier\util;


class FieldFromUri {

  public static function currentUriField(){
    $currentUri = \Drupal::request()->getRequestUri();
    return explode('.', explode('/', $currentUri)[7])[2];
  }
}