<?php

namespace Drupal\entity_pilot;

/**
 * A trait for accessing the legacy drupal_set_message() function.
 */
trait LegacyMessagingTrait {

  /**
   * Wraps drupal_set_message().
   */
  public static function setMessage($message = NULL, $type = 'status') {
    if (function_exists('drupal_set_message')) {
      drupal_set_message($message, $type);
    }
  }

}
