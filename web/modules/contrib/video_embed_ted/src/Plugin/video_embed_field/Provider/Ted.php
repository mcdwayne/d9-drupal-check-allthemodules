<?php

/**
 * @file
 * Contains \Drupal\video_embed_ted\Plugin\video_embed_field\Provider\Ted.
 */

namespace Drupal\video_embed_ted\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;
use Guzzle\Http\Client;
use Drupal\Component\Utility\SafeMarkup;

/**
 * @VideoEmbedProvider(
 *   id = "ted",
 *   title = @Translation("Ted")
 * )
 */
class Ted extends ProviderPluginBase {

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
        'scrolling' => 'no',
        'webkitAllowFullScreen' => 'webkitAllowFullScreen',
        'mozallowfullscreen' => 'mozallowfullscreen',
        'allowfullscreen' => 'allowfullscreen',
        'src' => sprintf('https://embed.ted.com/talks/%s', $this->getVideoId()),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    $image_url = $this->ted_get_thumbnail_url($this->getVideoId());
    if ($image_url) {
      return $image_url;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    $matches = array();
    preg_match('#/talks/([A-Za-z0-9_-]+)(\.html)?#', $input, $matches);
    if ($matches && !empty($matches[1])) {
      // Regex is matching just alphanumeric characters, no need for security
      // escape.
      return $matches[1];
    }
    // Otherwise return FALSE.
    return FALSE;
  }
  
  /**
   * Get url for thumbnail.
   *
   * @return boolean|array
   *   Image url or False.
   */
  public function ted_get_thumbnail_url() {
    // We limit thumbnail size just in case, this is still bigger than default.
    $oembed_url = 'http://www.ted.com/talks/oembed.json?url=' . urlencode($this->getInput()) . '&maxwidth=2000&maxheight=2000';
    $client = \Drupal::httpClient();
    $oembed = json_decode($client->get($oembed_url)->getBody(), TRUE);
    if (!empty($oembed['thumbnail_url'])) {
      return $oembed['thumbnail_url'];
    }
    return FALSE;
  }

}
