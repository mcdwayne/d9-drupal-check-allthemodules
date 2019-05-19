<?php

namespace Drupal\video_embed_spotify\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * Provides Spotify plugin.
 *
 * @VideoEmbedProvider(
 *   id = "spotify",
 *   title = @Translation("Spotify")
 * )
 */
class Spotify extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    return [
      '#type' => 'video_embed_iframe',
      '#provider' => 'spotify',
      '#url' => sprintf('https://open.spotify.com/embed/%s'/*?theme=white'*/, $this->getVideoId()),
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
      ],
    ];
  }

  /**
   * Get the vimeo oembed data.
   *
   * @return array
   *   An array of data from the oembed endpoint.
   */
  protected function oEmbedData() {
    return json_decode(file_get_contents('https://open.spotify.com/oembed?url=' . $this->getInput()));
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    return $this->oEmbedData()->thumbnail_url;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalThumbnailUri() {
    return $this->thumbsDirectory . '/' . str_replace('/', '', $this->getVideoId()) . '.jpg';
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    preg_match('/^https:\/\/open\.spotify\.com\/(?P<id>[0-9a-zA-Z\/]*)$/', $input, $matches);
    return isset($matches['id']) ? $matches['id'] : FALSE;
  }

}
