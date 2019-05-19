<?php

namespace Drupal\steam_api;

/**
 * ISteamNews interface.
 */
interface ISteamNewsInterface {

  /**
   * Get news for the specified steam app.
   *
   * @param string $appid
   *   Steam AppID to retrieve news for.
   * @param int $count
   *   How many news we want.
   * @param string $feed_names
   *   Comma-seperated list of feed names to return news for.
   *
   * @return array
   *   The news for the specified app.
   */
  public function getNewsForApp(string $appid, int $count, string $feed_names);

}
