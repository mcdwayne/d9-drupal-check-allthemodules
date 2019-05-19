<?php

/**
 * @file
 * Contains \Drupal\video_embed_screencast\Plugin\video_embed_field\Provider\Screencast.
 */

namespace Drupal\video_embed_screencast\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * @VideoEmbedProvider(
 *   id = "screencast",
 *   title = @Translation("Screencast")
 * )
 */
class Screencast extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    return [
      '#type' => 'video_embed_iframe',
      '#provider' => 'screencast',
      '#url' => sprintf('http://www.screencast.com/users/%s/embed', $this->getVideoId()),
      '#query' => [
        'autoplay' => $autoplay
      ],
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    $image_url = $this->screencast_get_thumbnail_url($this->getVideoId());
    if ($image_url) {
      return $image_url;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    //ignore links if not contain screencast domain
    if(!preg_match('/(screencast.com)\//', $input)){
      return FALSE;
    }
    if(preg_match('/^http(s)?:\/\/(www\.)?(screencast.com)\/(?<path>users|t|api)\/(?<id>.*)(?<embed>\/embed)?$/', $input, $matches)){
      if(!empty($matches['path'])){
        switch ($matches['path']) {
          // screencast.com/users/ link type
          case 'users':
            return $matches['id'];
            break;
          // screencast.com/t/ link type
          case 't':
            $client = \Drupal::httpClient();
            try {
              $response = $client->request('GET', $input, ['allow_redirects' => false, 'verify' => false]);
            }
            catch (RequestException $e) {
              watchdog_exception('video_embed_screencast', $e->getMessage());
            }
            $response_body = $response->getBody();
            if (!empty($response_body)) {
              // Get video link <iframe class="embeddedObject src="screencast.com/users/GET_ID">
              preg_match('/<iframe(.*)? src="http(s)?:\/\/(www\.)?(screencast.com)\/(?<path>users)\/(?<id>.*)(?<embed>\/embed)"/', $response_body, $matches);
              if(isset($matches['id'])){
                return $matches['id'];
              }
            }
            break;
          // screencast.com/media/ link type NOT supported
          case 'media':
            return FALSE;
            break;
        }
      }
    }
    return FALSE;
  }

  /**
   *
   * @param type $id
   * @return boolean|array
   */
  public static function screencast_get_thumbnail_url($id) {
    $url = sprintf('http://www.screencast.com/users/%s/embed', $id);
    if(preg_match('/^http(s)?:\/\/(www\.)?/(screencast.com)\/(users)\/(?<id>.*)/embed$/', $url, $matches)){
      $client = \Drupal::httpClient();
      $response = $client->get($url);
      $response_body = $response->getBody();
      if (!empty($response_body)) {
        // Get image from TSC.mediaInterface.posterSrc = 'GET_VALUE';
        preg_match("/TSC.mediaInterface.posterSrc = '(?<thumbnail>.*)';/", $response_body, $matches);
        if (!empty($matches['thumbnail'])) {
          return $matches['thumbnail'];
        }
      }
    }
    return FALSE;
  }
}
