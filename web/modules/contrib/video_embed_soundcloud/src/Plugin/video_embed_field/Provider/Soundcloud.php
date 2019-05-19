<?php

/**
 * @file
 * Contains \Drupal\video_embed_soundcloud\Plugin\video_embed_field\Provider\Soundcloud.
 */

namespace Drupal\video_embed_soundcloud\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;

/**
 * @VideoEmbedProvider(
 *   id = "soundcloud",
 *   title = @Translation("Soundcloud")
 * )
 */
class Soundcloud extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {

    $video_id = $this->getVideoId();
    $video_explode = explode("<>", $video_id);
    $track_id = $video_explode[0];
    $track_url = "https%3A//api.soundcloud.com/tracks/" . $track_id;

    return [
      '#type' => 'video_embed_iframe',
      '#provider' => 'soundcloud',
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
        'src' => sprintf('https://w.soundcloud.com/player/?visual=true&url=%s&autoplay=%d', $track_url, $autoplay),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    $thumbnail_url = explode("<>", $this->getVideoId());
    return $thumbnail_url[1];
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalThumbnailUri() {
    $thumbnail_url = explode("<>", $this->getVideoId());
    return $this->thumbsDirectory . '/' . $thumbnail_url[0] . '.jpg';
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {

    $options = [
      'query' => ['url' => $input, 'format' => 'json'],
    ];
    $oembed_url = Url::fromUri('http://soundcloud.com/oembed', $options)->toString();

    try {
      $data = (string) \Drupal::httpClient()->post($oembed_url, array('http_errors' => FALSE))->getBody();
      $json_decode_data = Json::decode($data);

      if (isset($json_decode_data['html'])) {
        $html_data = htmlentities($json_decode_data['html']);
        $strip_data = strip_tags($html_data);
        $decode_data = urldecode($strip_data);
        $matches = explode("/", $decode_data);

        if (count($matches) == 10) {
          $track_array = explode("&", $matches[8]);
          $track_id = $track_array[0];
          $track_thumbnail = $track_id . "<>" . $json_decode_data['thumbnail_url'];
          return $track_thumbnail;
        }
        else {
          return FALSE;
        }

      }
      else {
        return FALSE;
      }

    }
    catch (Exception $ex) {
      return FALSE;
    }

  }

}
