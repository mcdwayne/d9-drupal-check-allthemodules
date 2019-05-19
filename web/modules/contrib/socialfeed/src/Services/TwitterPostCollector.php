<?php

namespace Drupal\socialfeed\Services;

use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * Class TwitterPostCollector.
 *
 * @package Drupal\socialfeed\Services
 */
class TwitterPostCollector {

  /**
   * Twitter application consumer key.
   *
   * @var string
   */
  protected $consumerKey;

  /**
   * Twitter application consumer secret.
   *
   * @var string
   */
  protected $consumerSecret;

  /**
   * Twitter application access token.
   *
   * @var string
   */
  protected $accessToken;

  /**
   * Twitter application access token secret.
   *
   * @var string
   */
  protected $accessTokenSecret;

  /**
   * Twitter OAuth client.
   *
   * @var \Abraham\TwitterOAuth\TwitterOAuth
   */
  protected $twitter;

  /**
   * TwitterPostCollector constructor.
   *
   * @param string $consumerKey
   *   $consumerKey.
   * @param string $consumerSecret
   *   $consumerSecret.
   * @param string $accessToken
   *   $accessToken.
   * @param string $accessTokenSecret
   *   $accessTokenSecret.
   * @param \Abraham\TwitterOAuth\TwitterOAuth|null $twitter
   *   Twitter OAuth Client.
   */
  public function __construct($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret, TwitterOAuth $twitter = NULL) {
    $this->consumerKey = $consumerKey;
    $this->consumerSecret = $consumerSecret;
    $this->accessToken = $accessToken;
    $this->accessTokenSecret = $accessTokenSecret;
    $this->twitter = $twitter;
    $this->setTwitterClient();
  }

  /**
   * Retrieve Tweets from the given accounts home page.
   *
   * @param int $count
   *   The number of posts to return.
   *
   * @return array
   *   An array of posts.
   */
  public function getPosts($count) {
    return $this->twitter->get('statuses/user_timeline', ['count' => $count]);
  }

  /**
   * Set the Twitter client.
   */
  public function setTwitterClient() {
    if (NULL === $this->twitter) {
      $this->twitter = new TwitterOAuth(
        $this->consumerKey,
        $this->consumerSecret,
        $this->accessToken,
        $this->accessTokenSecret
      );
    }
  }

}
