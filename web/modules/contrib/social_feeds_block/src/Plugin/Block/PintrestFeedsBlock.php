<?php

namespace Drupal\social_feeds_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Pintrest feeds' block.
 *
 * @Block(
 *   id = "pintrest_feeds_block",
 *   admin_label = @Translation("Pintrest social feeds")
 * )
 */
class PintrestFeedsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $values = \Drupal::config('config.pintrest_social_feeds');

    $pintrest_user_name = $values->get('pintrest_user_name');
    if (isset($pintrest_user_name)) {
      $url = "http://api.pinterest.com/v3/pidgets/users/" . $pintrest_user_name . "/pins?limit=2";
      $response = \Drupal::httpClient()->get($url, array('headers' => array('Accept' => 'text/json')));
      $feeds = $response->getBody()->getContents();

      $feeds = json_decode($feeds, TRUE);
      $feed_pin_data = $feeds['data']['pins'];

      if ($feeds['status'] != 'success') {
        $error_message = 'Wrong pintrest User Name .';
      }
    }
    else {
      $error_message = "pintrest User Name Missing.";
    }

    return array(
      '#theme' => 'pintrest_social_feeds_block',
      '#data' => $feed_pin_data,
      '#error_message' => $error_message,
    );
  }

}
