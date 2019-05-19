<?php

/**
 * @file
 * Contains \Drupal\video_embed_peertube\Plugin\video_embed_field\Provider\Peertube.
 */

namespace Drupal\video_embed_peertube\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * @VideoEmbedProvider(
 *   id = "peertube",
 *   title = @Translation("Peertube")
 * )
 */
class Peertube extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    $input = $this->getInput();
    $domain = self::extractInstanceSchemeDomain($input);
    return [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
          'src' => sprintf($domain . '/videos/embed/%s?autoplay=%d', $this->getVideoId(), $autoplay),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    $input = $this->getInput();
    $domain = self::extractInstanceSchemeDomain($input);
    return sprintf($domain . '/static/previews/%s.jpg', $this->getVideoId());
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    $matches = NULL;
    $scheme_domain = self::extractInstanceSchemeDomain($input);
    if (self::isPeerTubeInstance($scheme_domain)) {
      // Extract the video Id.
      $domain = self::extractInstanceDomain($input);
      preg_match('/^https?:\/\/(www\.)?' . $domain. '\/videos\/watch\/(?<id>[0-9A-Za-z_-]*)/', $input, $matches);
    }
    return isset($matches['id']) ? $matches['id'] : FALSE;
  }

  /**
   * Function to extract the domain name.
   *
   * @param string $input
   *   The video URL.
   *
   * @return string
   *   The domain name.
   */
  public static function extractInstanceDomain($input) {
    $info = parse_url($input);
    return !empty($info['host']) ? $info['host'] : '';
  }

  /**
   * Function to extract the domain name with the scheme.
   *
   * @param string $input
   *   The video URL.
   *
   * @return string
   *   The domain name with the scheme.
   */
   public static function extractInstanceSchemeDomain($input) {
    $info = parse_url($input);
    return !empty($info['host'] && !empty($info['scheme'])) ? $info['scheme'] . '://' . $info['host'] : '';
  }

  /**
   * Function to determine if the website is a Peertube instance.
   *
   * @param string $domain
   *   The video URL.
   *
   * @return boolean
   *   Is a Peertube instance.
   */
  public static function isPeerTubeInstance($domain) {
    // Do a call on the Peertube API in order to know if it is a Peertube instance.
    $peertube_url_info = $domain . '/api/v1/config';
    $json = file_get_contents($peertube_url_info);
    $obj = json_decode($json);
    return !empty($obj) && !empty($obj->instance);
  }

}
