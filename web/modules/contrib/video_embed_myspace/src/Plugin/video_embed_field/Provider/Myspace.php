<?php

/**
 * @file
 * Contains \Drupal\video_embed_myspace\Plugin\video_embed_field\Provider\Myspace.
 */

namespace Drupal\video_embed_myspace\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * @VideoEmbedProvider(
 *   id = "myspace",
 *   title = @Translation("Myspace")
 * )
 */
class Myspace extends ProviderPluginBase {

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
        'src' => sprintf('//media.myspace.com/play/video/%s', $this->getVideoId()),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    throw new \Exception('MySpace does not support thumbnails.');
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    preg_match('/https?:\/\/(www\.)?myspace.com\/(.*)\/video\/(.*)\/(?<id>[0-9]*)/', $input, $matches);
    return isset($matches['id']) ? $matches['id'] : FALSE;
  }

}
