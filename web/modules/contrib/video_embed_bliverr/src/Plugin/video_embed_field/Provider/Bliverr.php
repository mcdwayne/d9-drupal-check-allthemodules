<?php

/**
 * @file
 * Contains \Drupal\video_embed_bliverr\Plugin\video_embed_field\Provider\Bliverr.
 */

namespace Drupal\video_embed_bliverr\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * @VideoEmbedProvider(
 *   id = "bliverr",
 *   title = @Translation("Bliverr")
 * )
 */
class Bliverr extends ProviderPluginBase {

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
        'src' => sprintf('//www.bliverr.com/embed/%s', $this->getVideoId()),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    return sprintf('//static.bliverr.com/assets/thumbs/%s_thumbnail.png', $this->getVideoId());
  }
  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    preg_match('/^https?:\/\/(www\.)?bliverr.com\/(.*)\/video\/(?<id>[a-z0-9]{13}){1}$/', $input, $matches);
    return isset($matches['id']) ? $matches['id'] : FALSE;
  }

}
