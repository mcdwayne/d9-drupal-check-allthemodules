<?php

namespace Drupal\social_feeds_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Facebook feeds' block.
 *
 * @Block(
 *   id = "fb_feeds_block",
 *   admin_label = @Translation("Facebook social feeds")
 * )
 */
class FacebookFeedsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $values = \Drupal::config('config.facebook_social_feeds_block');

    $fb_app_name = $values->get('fb_app_name');
    $fb_app_id = $values->get('fb_app_id');
    $fb_secret_id = $values->get('fb_secret_id');
    $fb_no_feeds = $values->get('fb_no_feeds');

    if (isset($fb_app_name) && isset($fb_app_name) && isset($fb_app_id) && isset($fb_secret_id)) {
      $fbfeeds = "https://graph.facebook.com/" . $fb_app_name . "/feed?access_token=" . $fb_app_id . "|" . $fb_secret_id . '&fields=link,message,description&limit=' . $fb_no_feeds;
      $response = \Drupal::httpClient()->get($fbfeeds, array('headers' => array('Accept' => 'text/plain')));
      $data = $response->getBody();
      $obj = json_decode($data, TRUE);
      $fb_feeds = $obj['data'];

    }
    else {
      $error_message = 'API Cridentials are Missing.';
    }

    return array(
      '#theme' => 'fb_social_feeds_block',
      '#data' => $fb_feeds,
      '#error_message' => $error_message,
    );
  }

}
