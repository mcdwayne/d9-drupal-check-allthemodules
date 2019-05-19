<?php

namespace Drupal\twitter_api;

/**
 * Interface TwitterApiClientInterface.
 */
interface TwitterApiClientInterface {

  /**
   * Gets the latest tweets for a given user.
   *
   * @param array $params
   *   Paramaters to send to the Twitter API.
   *
   * @return array
   *   The decoded response.
   */
  public function getTweets(array $params);

  /**
   * Send a GET query to a specific endpoint with an optional array of params.
   *
   * @param string $end_point
   *   The url endpoint e.g statuses/user_timeline.json.
   * @param array $query_params
   *   An optional array of query parameters.
   *
   * @return array
   *   The decoded JSON response.
   */
  public function doGet($end_point, array $query_params);

}
