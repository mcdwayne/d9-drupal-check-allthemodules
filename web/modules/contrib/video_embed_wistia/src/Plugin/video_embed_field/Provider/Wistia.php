<?php

/**
 * @file
 * Contains \Drupal\video_embed_wistia\Plugin\video_embed_field\Provider\Wistia.
 */

namespace Drupal\video_embed_wistia\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * @VideoEmbedProvider(
 *   id = "wistia",
 *   title = @Translation("Wistia")
 * )
 */
class Wistia extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    return [
      '#type' => 'video_embed_iframe',
      '#provider' => 'wistia',
      '#url' => sprintf('https://fast.wistia.com/embed/iframe/%s', $this->getVideoId()),
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
    $video_data = json_decode(file_get_contents('http://fast.wistia.net/oembed?url='. $this->getInput() .'?embedType=async'));
    return $video_data->thumbnail_url;
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    preg_match('/^https?:\/\/(.+)?(wistia.com|wi.st)\/(medias|embed)\/(?<id>[0-9A-Za-z]+)$/', $input, $matches);
    return isset($matches['id']) ? $matches['id'] : FALSE;
  }
}