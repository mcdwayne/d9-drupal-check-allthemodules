<?php

/**
 * @file
 * Contains \Drupal\video_embed_rutube\Plugin\video_embed_field\Provider\Rutube.
 */

namespace Drupal\video_embed_rutube\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * @VideoEmbedProvider(
 *   id = "rutube",
 *   title = @Translation("Rutube")
 * )
 */
class Rutube extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    return [
      '#type' => 'inline_template',
      '#template' => $this->oEmbedData('html')
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    return $this->oEmbedData('thumbnail_url');
  }

  /**
   * Get the oEmbed data for this video.
   *
   * @param string|bool $key
   *   An optional key to retrieve.
   *
   * @return object
   *   An oEmbed object.
   */
  protected function oEmbedData($key = FALSE) {
    // @todo, does this need caching or does render cache handle it?
    $contents = file_get_contents(sprintf('http://rutube.ru/api/oembed/?url=http://rutube.ru/video/%s/&format=json', $this->getVideoId()));
    $data = json_decode($contents, TRUE);
    return $key ? $data[$key] : $data;
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    preg_match('/https?:\/\/(www\.)?rutube.ru\/video\/(?<id>[a-z0-9]*)(.*?)/', $input, $matches);
    return isset($matches['id']) ? $matches['id'] : FALSE;
  }

}
