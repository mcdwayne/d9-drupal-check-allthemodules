<?php

namespace Drupal\video_embed_vzaar\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * A Vzaar provider plugin for video embed field.
 *
 * @VideoEmbedProvider(
 *   id = "vzaar",
 *   title = @Translation("vzaar")
 * )
 */
class vzaar extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    $iframe = [
      '#type' => 'video_embed_iframe',
      '#provider' => 'vzaar',
      '#url' => sprintf('https://view.vzaar.com/%s/player', $this->getVideoId()),
      '#query' => [],
      '#attributes' => [
        'id' => sprintf('vzvd-%s', $this->getVideoId()),
        'name' => sprintf('vzvd-%s', $this->getVideoId()),
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
      ],
    ];
    return $iframe;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    $url = 'https://view.vzaar.com/%s/thumb';
    return sprintf($url, $this->getVideoId());
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    if (strpos($input, 'view.vzaar.com') !== false) {
      // e.g. https://view.vzaar.com/848450 
      $id= preg_replace('/[^0-9]/','',$input);
      return isset($id) ? $id : false;
    }
    return false;
  }
}
