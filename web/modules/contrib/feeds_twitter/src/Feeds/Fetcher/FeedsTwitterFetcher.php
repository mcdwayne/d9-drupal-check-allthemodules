<?php

namespace Drupal\feeds_twitter\Feeds\Fetcher;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\feeds\FeedInterface;
use Drupal\feeds\Plugin\Type\Fetcher\FetcherInterface;
use Drupal\feeds\Plugin\Type\ClearableInterface;
use Drupal\feeds\Plugin\Type\PluginBase;
use Drupal\feeds\Result\RawFetcherResult;
use Drupal\feeds\StateInterface;
use Abraham\TwitterOAuth\TwitterOAuth;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Constructs FeedsTwitterFetcher object.
 *
 * @FeedsFetcher(
 *   id = "feeds_twitter_fetcher",
 *   title = @Translation("Twitter"),
 *   description = @Translation("Fetch tweets and retweets from a Twitter user"),
 *   form = {
 *     "configuration" = "Drupal\feeds_twitter\Feeds\Fetcher\Form\FeedsTwitterFetcherForm",
 *     "feed" = "Drupal\feeds_twitter\Feeds\Fetcher\Form\FeedsTwitterFetcherFeedForm",
 *   }
 * )
 */
class FeedsTwitterFetcher extends PluginBase implements ClearableInterface, FetcherInterface {

  /**
   * {@inheritdoc}
   */
  public function fetch(FeedInterface $feed, StateInterface $state) {
    $result = $this->get($feed->getSource());
    if ($result !== FALSE) {
      return new RawFetcherResult($result);
    }
    else {
      return new RawFetcherResult('');
    }
  }

  /**
   * Make the API queries to get the data the parser needs.
   *
   * @param ImmutableConfig $config
   *   Drupal Config object.
   *
   * @return string
   *   Returns an JSON encoded array of stdClass objects.
   */
  public function get(String $source) {
    $config = $this->getConfiguration();

    // Fetch tweets
    $twitter = new TwitterOAuth($config['api_key'], $config['api_secret_key'], $config['access_token'], $config['access_token_secret']);
    $twitter->setDecodeJsonAsArray(TRUE);
    $tweets = $twitter->get('statuses/user_timeline', ['screen_name' => $source, 'count' => $config['fetch_quantity'], 'include_rts' => $config['include_retweets'], 'tweet_mode' => 'extended']);

    // Return full API response if configured to do so.
    if ($config['use_simplified_json'] == FALSE) {
      return $tweets->getBody();
    }

    // Compile links, tags, user mentions, and we'll ignore media for now.
    $compiled_tweets = [];
    foreach ($tweets as $tweet) {
      $retweet = (isset($tweet['retweeted_status']) && !empty($tweet['retweeted_status']));
      $created_unix = strtotime($tweet['created_at']);
      $compiled_tweets[] = [
        'id' => $tweet['id'],
        'is_retweet' => $retweet ? 1 : 0,
        'name' => $tweet['user']['name'],
        'screen_name' => $tweet['user']['screen_name'],
        'tweet_author_name' => $retweet ? $tweet['retweeted_status']['user']['name'] : $tweet['user']['name'],
        'tweet_author_screen_name' => $retweet ? $tweet['retweeted_status']['user']['screen_name'] : $tweet['user']['screen_name'],
        'created_at' => $created_unix,
        'display_date' => $retweet ? strtotime($tweet['retweeted_status']['created_at']) : $created_unix,
        'text' => $retweet ? $this->compileTweet($tweet['retweeted_status']['full_text'], $tweet['retweeted_status']['entities']) : $this->compileTweet($tweet['full_text'], $tweet['entities']),
        'suggested_title' => ($retweet ? 'Retweet of ' . $tweet['retweeted_status']['user']['screen_name'] : 'Tweet') . ' by ' . $tweet['user']['screen_name'] . ' on ' . date('Y-m-d', $created_unix),
      ];
    }

    return json_encode($compiled_tweets);
  }

  /**
   * Take entities given by Twitter and replace plain text with elements.
   */
  private function compileTweet(String $text, Array $entities) {
    // Replace hashtags.
    foreach ($entities['hashtags'] as $hashtag) {
      $text = $this->entityReplace('#' . $hashtag['text'], 'https://twitter.com/hashtag/' . $hashtag['text'], $text);
    }

    // Replace mentions.
    foreach ($entities['user_mentions'] as $mention) {
      $text = $this->entityReplace('@' . $mention['screen_name'], 'https://twitter.com/' . $mention['screen_name'], $text);
    }

    // Replace URLs.
    foreach ($entities['urls'] as $url) {
      $text = $this->entityReplace($url['url'], $url['url'], $text);
    }

    // Replace media.
    foreach ($entities['media'] as $media) {
      $text = $this->entityReplace($media['url'], $media['url'], $text);
    }

    return $text;
  }

  /**
   * Helper function.
   * Takes entity value, uri, and replaces them in text.
   */
  private function entityReplace(String $entity, String $uri, String $text) {
    $url = Url::fromUri($uri);
    $url->setOption('attributes', [
      'target' => '_blank',
      'rel' => 'noopener noreferrer',
    ]);
    $link = Link::fromTextAndUrl($entity, $url)->toString();
    return str_replace($entity, $link, $text);
  }

  /**
   * {@inheritdoc}
   */
  public function clear(FeedInterface $feed, StateInterface $state) {
    $this->onFeedDeleteMultiple([$feed]);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'api_key' => '',
      'api_secret_key' => '',
      'access_token' => '',
      'access_token_secret' => '',
      'fetch_quantity' => 3,
      'include_retweets' => true,
      'use_simplified_json' => true
    ];
  }

}