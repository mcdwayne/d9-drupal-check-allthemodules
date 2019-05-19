<?php

/**
 * @file
 * Contains \Drupal\video_wistia\Plugin\video\Provider\Wistia.
 */

namespace Drupal\video_wistia\Plugin\video\Provider;

use Drupal\video\ProviderPluginBase;

/**
 * @VideoEmbeddableProvider(
 *   id = "wistia",
 *   label = @Translation("Wistia"),
 *   description = @Translation("Wistia Video Provider"),
 *   regular_expressions = {
 *     "@https?:\/\/(.+)?(wistia.com|wi.st)\/(medias|embed)/(?<id>[0-9A-Za-z]+)@i",
 *   },
 *   mimetype = "video/wistia",
 *   stream_wrapper = "wistia"
 * )
 */
class Wistia extends ProviderPluginBase {
  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($settings) {
    $file = $this->getVideoFile();
    $data = $this->getVideoMetadata();
    return [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'width' => $settings['width'],
        'height' => $settings['height'],
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
        'src' => sprintf('//fast.wistia.net/embed/iframe/%s?version=v1&videoHeight=%d&videoWidth=%d', $data['id'], $settings['height'], $settings['width']),
      ],
      '0' => array(
        '#type' => 'html_tag',
        '#tag' => 'script',
        '#attributes' => array(
             'type' => 'text/javascript',
             'src' => '//fast.wistia.net/assets/external/E-v1.js',
             'async',
             'defer'
        ),
        '#value' => '',
      ),
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    $data = $this->getVideoMetadata();
    $video_data = json_decode(file_get_contents('https://fast.wistia.net/oembed?url=http://home.wistia.com/medias/' . $data['id']));
    return $video_data->thumbnail_url;
  }
}