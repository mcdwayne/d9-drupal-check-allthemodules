<?php

namespace Drupal\last_tweets\Gateway;

use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * Class LastTweetsGateway.
 *
 * @package Drupal\last_tweets\Gateway
 */
class LastTweetsGateway {

  /**
   * Gets tweets.
   *
   * @param int|string $twitter_limit
   *   Number of displayed tweets.
   * @param array $settings
   *   Settings array.
   *
   * @return array
   *   Tweets array.
   */
  public function getLastTweets($twitter_limit, array $settings) {

    // Create the connection.
    $twitter = new TwitterOAuth($settings['consumer_key'],
      $settings['secret_key'], $settings['access_token'],
      $settings['access_token_secret']);

    // Migrate over to SSL/TLS.
    $twitter->ssl_verifypeer = TRUE;

    // Load the Tweets.
    return $twitter->get('statuses/user_timeline', [
      'tweet_mode' => 'extended',
      'screen_name' => $settings['twitter_username'],
      'exclude_replies' => 'false',
      'include_rts' => 'true',
      'include_entities' => 'true',
      'count' => $twitter_limit,
    ]);
  }
}