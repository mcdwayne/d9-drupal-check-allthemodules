<?php

namespace Drupal\pubg_api;

/**
 * Pubg Api Base Interface.
 */
interface PubgApiBaseInterface {

  /**
   * Get API response.
   *
   * @param string $shard
   *   A valid PUBG shard.
   * @param string $api_endpoint
   *   The PUBG API endpoint we want to call.
   * @param array $endpoint_options
   *   Options we want to pass to that API endpoint.
   *
   * @return array|string
   *   The API call response or an error message.
   *
   * @see https://documentation.playbattlegrounds.com/en/making-requests.html
   */
  public function getResponse(string $shard, string $api_endpoint, array $endpoint_options = []);

}
