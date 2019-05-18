<?php

namespace Drupal\adminrss;

use Drupal\Component\Utility\Random;

/**
 * Class AdminRss contains constants used at various places in the module.
 */
class AdminRss {

  /**
   * The name of the configuration object for the module.
   */
  const CONFIG = 'adminrss.settings';

  /**
   * The name of the single setting within the configuration object.
   */
  const TOKEN = 'token';

  /**
   * The main feed route.
   */
  const ROUTE_MAIN = 'adminrss.feed_controller_feedAction';

  /**
   * The settings route.
   */
  const ROUTE_SETTINGS = 'adminrss.admin_rss_settings_form';

  /**
   * Generate and store a new AdminRSS token.
   *
   * @param null|string $token
   *   Optional. A new token to set. If NULL, a new one will be generated.
   *
   * @return string
   *   The value of the new already saved token.
   */
  public static function saveNewToken($token = NULL) {
    if (empty($token)) {
      $random = new Random();
      $used_token = $random->name(16, TRUE);
    }
    else {
      $used_token = $token;
    }

    \Drupal::configFactory()
      ->getEditable(static::CONFIG)
      ->set(static::TOKEN, $used_token)
      ->save();

    return $used_token;
  }

}
