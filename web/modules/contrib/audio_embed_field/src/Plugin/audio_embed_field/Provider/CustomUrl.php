<?php

namespace Drupal\audio_embed_field\Plugin\audio_embed_field\Provider;

use Drupal\audio_embed_field\ProviderPluginBase;

/**
 * A custom URL provider plugin.
 *
 * @AudioEmbedProvider(
 *   id = "custom_url",
 *   title = @Translation("Custom URL")
 * )
 */
class CustomUrl extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    $embed_code = [
      '#type' => 'audio_embed_html5',
      '#provider' => 'custom_url',
      '#url' => $this->getInput(),
    ];

    return $embed_code;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {

    if (strpos($input, 'http://') !== FALSE || strpos($input, 'https://') !== FALSE) {

      if (strpos($input, '.mp3') !== FALSE) {
        return 'mp3';
      }

      if (strpos($input, '.mp4') !== FALSE) {
        return 'mp4';
      }

      if (strpos($input, '.m4a') !== FALSE) {
        return 'm4a';
      }

      if (strpos($input, '.aac') !== FALSE) {
        return 'aac';
      }

      if (strpos($input, '.ogg') !== FALSE) {
        return 'ogg';
      }

      if (strpos($input, '.oga') !== FALSE) {
        return 'oga';
      }

      if (strpos($input, '.wav') !== FALSE) {
        return 'wav';
      }

    }
    return NULL;
  }

}
