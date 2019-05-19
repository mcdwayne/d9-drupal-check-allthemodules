<?php

namespace Drupal\social_feed_fetcher\Plugin\SocialDataProvider;


use Abraham\TwitterOAuth\TwitterOAuth;
use Drupal\social_feed_fetcher\SocialDataProviderPluginBase;

/**
 * Class TwitterDataProvider
 *
 * @package Drupal\social_feed_fetcher\Plugin\SocialDataProvider
 *
 * @SocialDataProvider(
 *   id = "twitter",
 *   label = @Translation("Twitter data provider")
 * )
 */
class TwitterDataProvider extends SocialDataProviderPluginBase {

  /**
   * Twitter OAuth client.
   *
   * @var \Abraham\TwitterOAuth\TwitterOAuth
   */
  protected $twitter;

  /**
   * The timeline to use.
   *
   * @var string
   */
  protected $timelines;

  /**
   * The name of user page to import.
   *
   * @var string
   */
  protected $screenName;

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
    $config = [
      'count' => $count
    ];

    if ($this->screenName) {
      $config['screen_name'] = $this->screenName;
    }

    $config['tweet_mode'] = 'extended';

    return $this->twitter->get($this->timelines, $config);
  }

  /**
   * Set the Twitter client.
   */
  public function setClient() {
    if (NULL === $this->twitter) {
      $this->twitter = new TwitterOAuth(
        $this->config->get('tw_consumer_key'),
        $this->config->get('tw_consumer_secret'),
        $this->config->get('tw_access_token'),
        $this->config->get('tw_access_token_secret')
      );
    }
  }

  /**
   * @param $timeline
   * @param $name
   */
  public function setTimelines($timeline, $name) {
    switch ($timeline) {
      case 'mention':
        $this->timelines = "statuses/mentions_timeline";
        break;
      case 'user':
        $this->screenName = $name;
        $this->timelines = "statuses/user_timeline";
        break;
      default:
        $this->timelines = "statuses/home_timeline";
    }

  }

}
