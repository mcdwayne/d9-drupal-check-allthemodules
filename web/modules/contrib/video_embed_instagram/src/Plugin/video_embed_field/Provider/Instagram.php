<?php

/**
 * @file
 * Contains \Drupal\video_embed_instagram\Plugin\video_embed_field\Provider\Instagram.
 */

namespace Drupal\video_embed_instagram\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * @VideoEmbedProvider(
 *   id = "instagram",
 *   title = @Translation("Instagram")
 * )
 */
class Instagram extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    return [
      '#type' => 'video_embed_iframe',
      '#provider' => 'instagram',
      '#url' => sprintf('http://instagram.com/p/%s/embed', $this->getVideoId()),
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
    return sprintf('http://instagr.am/p/%s/media/?size=l', $this->getVideoId());
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    preg_match('/^https?:\/\/(www)?\.instagram\.com\/p\/(?<id>[a-zA-Z0-9]*)\/?/', $input, $matches);
    return isset($matches['id']) ? $matches['id'] : FALSE;
  }

}
