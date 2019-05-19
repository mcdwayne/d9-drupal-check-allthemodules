<?php

namespace Drupal\social_feeds_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Instagram feeds' block.
 *
 * @Block(
 *   id = "instagram_feeds_block",
 *   admin_label = @Translation("Instagram social feeds")
 * )
 */
class InstagramFeedsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $values = \Drupal::config('config.instagram_social_feeds_block');

    // $insta_client_id = $values->get('insta_client_id');
    // $insta_redirec_uri = $values->get('insta_redirec_uri');.
    $insta_access_token = $values->get('insta_access_token');
    $insta_pic_counts = $values->get('insta_pic_counts');

    // $i = 0;
    // $images = $pic = array();
    $url = "https://api.instagram.com/v1/users/self/media/recent/?access_token=" . $insta_access_token . '&count=' . $insta_pic_counts;

    $response = \Drupal::httpClient()->get($url, array('headers' => array('Accept' => 'text/json')));
    $request = $response->getBody()->getContents();

    $instagram_social_feeds = \Drupal::config('config.instagram_social_feeds_block');
    $insta_image_resolution = $instagram_social_feeds->get('insta_image_resolution');
    $insta_likes = $instagram_social_feeds->get('insta_likes');

    if (isset($insta_access_token) && !empty($insta_access_token)) {
      if ($request->status_message != 'BAD REQUEST' || $request->status_message != 'BAD REQUEST') {
        $json_response = json_decode($request, TRUE);

      }
      else {
        $error_message = 'The access token provided is invalid.';
      }
    }

    return array(
      '#theme' => 'instagram_social_feeds_block',
      '#data' => $json_response,
      '#config' => array('insta_image_resolution' => $insta_image_resolution, 'insta_likes' => $insta_likes),
      '#error_message' => $error_message,
    );
  }

}
