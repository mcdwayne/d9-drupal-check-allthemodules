<?php

namespace Drupal\social_feeds_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Twitter feeds' block.
 *
 * @Block(
 *   id = "twitter_feeds_block",
 *   admin_label = @Translation("Twitter social feeds")
 * )
 */
class TwitterFeedsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $values = \Drupal::config('config.twitter_social_feeds');

    $tw_consumer_key = $values->get('tw_consumer_key');
    $tw_consumer_secret = $values->get('tw_consumer_secret');
    $tw_user_name = $values->get('tw_user_name');
    $tw_counts = $values->get('tw_counts');
    if (isset($tw_consumer_key)) {
      // Auth Parameters.
      $api_key = urlencode($tw_consumer_key);
      $api_secret = urlencode($tw_consumer_secret);
      $auth_url = 'https://api.twitter.com/oauth2/token';

      // What we want?
      $data_username = $tw_user_name;
      $data_count = $tw_counts;
      $data_url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';

      // Get API Access Token.
      $api_credentials = base64_encode($api_key . ':' . $api_secret);
      $auth_headers = 'Authorization: Basic ' . $api_credentials . "\r\n" . 'Content-Type: application/x-www-form-urlencoded;charset=UTF-8' . "\r\n";
      $auth_context = stream_context_create(
        array(
          'http' => array(
            'header' => $auth_headers,
            'method' => 'POST',
            'content' => http_build_query(
            array(
              'grant_type' => 'client_credentials',
            )
            ),
          ),
        )
      );
      $auth_response = json_decode(file_get_contents($auth_url, 0, $auth_context), TRUE);
      $auth_token = $auth_response['access_token'];

      // Get Tweets.
      $data_context = stream_context_create(
        array(
          'http' => array(
            'header' => 'Authorization: Bearer ' . $auth_token . "\r\n",
          ),
        )
      );
      $twitter_values = json_decode(file_get_contents($data_url . '?count=' . $data_count . '&screen_name=' . urlencode($data_username), 0, $data_context), TRUE);

      // Results - Do what you want!
      // foreach ($twitter_values as $key => $twitter_value) {
      //
      //      $twitter_tweets[$key]['username'] = $twitter_value['user']['screen_name'];
      //      $twitter_tweets[$key]['full_username'] = 'http://twitter.com/' . $twitter_value['user']['screen_name'];
      //      preg_match_all('#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#', $twitter_value['text'], $extra_links);
      //
      //    foreach ($extra_links[0] as $extra_link) {
      //        $twitter_tweets[$key]['extra_links'][] = $extra_link;
      //    }
      //    if (isset($twitter_value['text'])) {
      //        $twitter_tweets[$key]['tweet'] = substr(rtrim($twitter_value['text'], $extra_link), 0, 100);
      //    }
      //
      // }
      // $twitter[] = $twitter_tweets;.
    }
    else {
      $error_message = 'API Cridentials are Misiing .';
    }
    return array(
      '#theme' => 'twitter_social_feeds_block',
      '#data' => $twitter_values,
      '#error_message' => $error_message,
    );
  }

}
