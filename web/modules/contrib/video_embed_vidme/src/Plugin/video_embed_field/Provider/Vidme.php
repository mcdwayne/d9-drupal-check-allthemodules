<?php

/**
 * @file
 * Contains \Drupal\video_embed_vidme\Plugin\video_embed_field\Provider\Vidme.
 */

namespace Drupal\video_embed_vidme\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;

/**
 * @VideoEmbedProvider(
 *   id = "vidme",
 *   title = @Translation("Vidme")
 * )
 */
class Vidme extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    $video_id = $this->getVideoId();
    $video_explode = explode("<>", $video_id);
    $track_id = $video_explode[1];

    return [
      '#type' => 'video_embed_iframe',
      '#provider' => 'vidme',
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
        'src' => $track_id,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    $thumbnail_url = explode("<>", $this->getVideoId());
    return $thumbnail_url[0];
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalThumbnailUri() {
    $thumbnail_url = explode("<>", $this->getVideoId());
    return $thumbnail_url[0];
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {

    $options = [
      'query' => ['url' => $input, 'format' => 'json'],
    ];
    $oembed_url = Url::fromUri('https://api.vid.me/videoByUrl', $options)->toString();

    try {
      $data = (string) \Drupal::httpClient()->post($oembed_url)->getBody();
      $json_decode_data = Json::decode($data);

      if (isset($json_decode_data['video'])) {

        $thumbnail_url = $json_decode_data['video']['thumbnail_url'];
        $embed_url = $json_decode_data['video']['embed_url'];

        $track_thumbnail = $thumbnail_url . "<>" . $embed_url;
        return $track_thumbnail;
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

