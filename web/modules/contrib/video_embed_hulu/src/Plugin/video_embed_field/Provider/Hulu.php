<?php

namespace Drupal\video_embed_hulu\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * @VideoEmbedProvider(
 *   id = "hulu",
 *   title = @Translation("Hulu")
 * )
 */
class Hulu extends ProviderPluginBase {

  /**
   * @var array|null
   */
  protected $oEmbedData = NULL;

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    // @todo, consider using the JavaScript version, however iframes are less
    // impact to page load and also don't grant JS access to your website to
    // Facebook.
    return [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
        'src' => $this->oembedData()['embed_url'],
      ],
    ];
  }

  protected function oembedData() {
    if (!isset($this->oembedData)) {
      $this->oEmbedData = json_decode(file_get_contents('http://www.hulu.com/api/oembed.json?url=http://www.hulu.com/watch/' . $this->getVideoId()), TRUE);
    }
    return $this->oEmbedData;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    return $this->oembedData()['large_thumbnail_url'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    if (preg_match('/hulu.com\/watch\/(\d+)/i', $input, $matches)) {
      return $matches[1];
    }
  }

}
