<?php

namespace Drupal\video_embed_google_drive\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * A Google Drive provider plugin for video embed field.
 *
 * @VideoEmbedProvider(
 *   id = "google_drive",
 *   title = @Translation("Google Drive")
 * )
 */
class GoogleDrive extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    preg_match('/^https?:\/\/drive.google.com\/(file\/d\/|open\?id=)(?<id>[0-9a-zA-Z-_]{2,64}).*$/', $input, $matches);
    return isset($matches['id']) ? $matches['id'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    $iframe = [
      '#type' => 'video_embed_iframe',
      '#provider' => 'google_drive',
      '#url' => sprintf('https://drive.google.com/file/d/%s/preview', $this->getVideoId()),
      '#query' => [],
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
      ],
    ];
    // TODO: is this supported by google drive?
    // if ($time_index = $this->getTimeIndex()) {
    //   $iframe['#fragment'] = sprintf('t=%s', $time_index);
    // }
    return $iframe;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    $url = 'https://drive.google.com/thumbnail?authuser=0&sz=w320&id=%s';
    return sprintf($url, $this->getVideoId());
  }

}
