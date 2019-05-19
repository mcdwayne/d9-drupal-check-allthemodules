<?php

namespace Drupal\video_embed_dreambroker\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * A Dreambroker provider plugin.
 *
 * @VideoEmbedProvider(
 *   id = "dreambroker",
 *   title = @Translation("Dreambroker")
 * )
 */
class Dreambroker extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    list($channel_id, $video_id) = $this->getVideoId();
    $embed_code = [
      '#type' => 'video_embed_iframe',
      '#provider' => 'dreambroker',
      '#url' => 'https://www.dreambroker.com/channel/' . $channel_id . '/iframe/' . $video_id,
      '#query' => [
        'autoplay' => $autoplay,
      ],
      '#attributes' => [
        'width' => $width,
        'height' => $height,
      ],
    ];
    return $embed_code;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    list($channel_id, $video_id) = $this->getVideoId();
    $url = "https://dreambroker.com/channel/$channel_id/$video_id/get/poster.png";
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalThumbnailUri() {
    list($channel_id, $video_id) = $this->getVideoId();
    return $this->thumbsDirectory . '/' . $video_id . '.png';
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    $matches = array();
    preg_match('#(?:https?:\/\/)?(?:www\.)?(?:dreambroker\.com\/(?:channel\/([a-z0-9]{8})\/([a-z0-9]{8})))#', $input, $matches);
    // Make sure there are values.
    if ($matches && !empty($matches[1]) && !empty($matches[2])) {
      // @TODO: Make sure Twig's auto-escaping is applied to the values.
      return array($matches[1], $matches[2]);
    }
    else {
      // Otherwise return FALSE.
      return FALSE;
    }
  }
}
