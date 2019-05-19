<?php

/**
 * @file
 * Contains \Drupal\video_embed_metacafe\Plugin\video_embed_field\Provider\Metacafe.
 */

namespace Drupal\video_embed_metacafe\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * @VideoEmbedProvider(
 *   id = "metacafe",
 *   title = @Translation("Metacafe")
 * )
 */
class Metacafe extends ProviderPluginBase {

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
        'src' => sprintf('http://www.metacafe.com/embed/%s/?ap=%d', $this->getVideoId(), $autoplay),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    throw new \Exception('Metacafe does not support thumbnails.');
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    preg_match('/https?:\/\/(www\.)?metacafe.com\/watch\/(?<id>[0-9]*)(.*?)/', $input, $matches);
    return isset($matches['id']) ? $matches['id'] : FALSE;
  }

}
