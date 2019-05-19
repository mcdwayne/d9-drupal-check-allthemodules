<?php

namespace Drupal\twitter_profile_widget;

/**
 * Interface TwitterProfileInterface.
 *
 * @package Drupal\twitter_profile_widget
 */
interface TwitterProfileInterface {

  /**
   * Pull tweets from the Twitter API.
   *
   * @param array $instance
   *   All the data for the given Twitter widget.
   *
   * @return str[]
   *   An array of Twitter objects.
   */
  public function pull(array $instance);

  /**
   * Helper query to check whether the credentials are valid.
   *
   * @param string $key
   *   Twitter API key.
   * @param string $secret
   *   Twitter API secret.
   *
   * @return bool|string
   *   Whether or not the connection is valid. If error message, return that.
   */
  public function checkConnection($key = '', $secret = '');

}
