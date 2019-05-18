<?php

namespace Drupal\freecaster\Plugin\video_embed_field\Provider;

use Drupal\freecaster\FcapiUtils;
use Drupal\video_embed_field\ProviderPluginBase;

/**
 * Freecaster video embed field provider.
 *
 * @VideoEmbedProvider(
 *   id = "freecaster",
 *   title = @Translation("Freecaster")
 * )
 */
class Freecaster extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    $autoplay_str = 'false';

    if ($autoplay) {
      $autoplay_str = 'true';
    }

    $return = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => 'video_' . $this->getVideoId() .'_container'
      ],
      '#attached' => [
        'library' => 'freecaster/freecaster_style'
      ]
    ];

    $return['#attached']['html_head'][] = [
        [
          '#type' => 'html_tag',
          '#tag' => 'script',
          '#attributes' =>
            array('src' => 'http://player.freecaster.com/embed/' . $this->getVideoId() . '.js?id=video_' . $this->getVideoId() . '&autoplay=' . $autoplay_str),

        ],
        'freecaster_video_' . $this->getVideoId()
    ];

    return $return;
  }

  /**
   * {@inheritdoc}
   *
   * @TODO : Peut-on récupérer une Thumbnail URL depuis l'API ?
   */
  public function getRemoteThumbnailUrl() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    $id = FALSE;
    // Parse_url is an easy way to break a url into its components.
    $parsed = parse_url($input);
    $path = $parsed['path'];
    $parts = explode('/', $path);
    $id = end($parts);
    return $id;
  }

}
