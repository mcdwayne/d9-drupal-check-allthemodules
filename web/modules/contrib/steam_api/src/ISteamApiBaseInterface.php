<?php

namespace Drupal\steam_api;

/**
 * Steam API base interface.
 */
interface ISteamApiBaseInterface {

  /**
   * Get API response.
   *
   * @param string $api_url
   *   The Steam API URL we want to call.
   * @param array $options
   *   Options we want to pass to that API call.
   *
   * @return array|string
   *   The API call response or an error message.
   */
  public function getResponse(string $api_url, array $options);

}
