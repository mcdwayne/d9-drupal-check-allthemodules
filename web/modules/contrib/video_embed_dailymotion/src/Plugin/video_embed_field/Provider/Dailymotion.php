<?php

/**
 * @file
 * Contains \Drupal\video_embed_dailymotion\Plugin\video_embed_field\Provider\Dailymotion.
 */

namespace Drupal\video_embed_dailymotion\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * @VideoEmbedProvider(
 *   id = "dailymotion",
 *   title = @Translation("Dailymotion")
 * )
 */
class Dailymotion extends ProviderPluginBase {

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
        'src' => sprintf('//www.dailymotion.com/embed/video/%s?autoPlay=%d', $this->getVideoId(), $autoplay),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    return sprintf('http://www.dailymotion.com/thumbnail/video/%s', $this->getVideoId());
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    preg_match('/^https?:\/\/(www\.)?dailymotion.com\/video\/(?<id>[a-z0-9]{6,7})(_([0-9a-zA-Z\-_])*)?$/', $input, $matches);
    return isset($matches['id']) ? $matches['id'] : FALSE;
  }

}
