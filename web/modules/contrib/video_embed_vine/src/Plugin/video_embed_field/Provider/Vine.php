<?php

/**
 * @file
 * Contains \Drupal\video_embed_vine\Plugin\video_embed_field\Provider\Vine.
 */

namespace Drupal\video_embed_vine\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * @VideoEmbedProvider(
 *   id = "vine",
 *   title = @Translation("Vine")
 * )
 */
class Vine extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    return [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
        'src' => sprintf('https://vine.co/v/%s/embed/simple?audio=%d', $this->getVideoId(), $autoplay),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    $video_data = json_decode(file_get_contents(sprintf('https://vine.co/oembed.json?id=%s', $this->getVideoId())));
    return $video_data->thumbnail_url;
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    preg_match('/^https?:\/\/vine.co\/v\/(?<id>[A-Za-z0-9]*)$/', $input, $matches);
    return isset($matches['id']) ? $matches['id'] : FALSE;
  }

}
