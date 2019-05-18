<?php
namespace Drupal\twitter_tweets\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\twitter_tweets\twitter_api_php\TwitterAPIExchange;
/**
* Provides a block for executing PHP code.
 *
 * @Block(
 *   id = "twitter_tweets_block",
 *   admin_label = @Translation("Twitter Tweets")
 * )
 */
class TweetsBlock extends BlockBase {  /**
   * Builds and returns the renderable array for this block plugin.
   *
   * @return array
   *   A renderable array representing the content of the block.
   *
   * @see \Drupal\block\BlockViewBuilder
   */
  public function build() {
    $config = \Drupal::config('twitter_tweets.credentials');
    $settings = array();
    $settings['oauth_access_token'] = $config->get('oauth_access_token');
    $settings['oauth_access_token_secret'] = $config->get('oauth_access_token_secret');
    $settings['consumer_key'] = $config->get('consumer_key');
    $settings['consumer_secret'] = $config->get('consumer_secret');
    $screen_name = $config->get('screen_name');
    $tweet_count = $config->get('tweet_count');
    $url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
    $getfield = '?screen_name='.$screen_name.'&count=' . $tweet_count;
    $requestMethod = 'GET';
    $twitter = new TwitterAPIExchange($settings);
    $tweets = $twitter->setGetfield($getfield) ->buildOauth($url, $requestMethod)->performRequest();

    $tweets = json_decode($tweets);
    foreach($tweets as $tweet) {
    # print_r($tweet);
    $tweet->text = check_markup($tweet->text, 'full_html');
    $cleanTweets[] = $tweet;
    }

    $params = array('tweets' => $cleanTweets);
    $tweet_template = array('#theme' => 'twitter_tweets_tweet_listing', '#params' => $params);
    return $tweet_template;
 }
 }