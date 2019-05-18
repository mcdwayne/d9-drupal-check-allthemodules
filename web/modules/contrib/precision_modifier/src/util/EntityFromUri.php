<?php

namespace Drupal\precision_modifier\util;

/**
 * A utility class that extracts the content type from the url
 *
 * @package Drupal\precision_modifier\Util
 */
class EntityFromUri
{
  /**
   * @return string id of the bundle or content type
   */
  public static function currentUriEntity()
  {
    $currentUri = \Drupal::request()->getRequestUri();
    return explode('/', $currentUri)[5];
  }
}