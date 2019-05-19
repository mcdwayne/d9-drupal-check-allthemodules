<?php

namespace Drupal\video_embed_vidyard\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;
use Drupal\Component\Utility\Html;

/**
 * A Vidyard provider plugin.
 *
 * @VideoEmbedProvider(
 *  id = "vidyard",
 *  title = @Translation("Vidyard")
 * )
 */
class Vidyard extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    return [
      '#type' => 'html_tag',
      '#tag' => 'script',
      '#attributes' => [
        'type' => 'text/javascript',
        'id' => 'vidyard_embed_code_' . $this->getVideoId(),
        'src' => sprintf('//play.vidyard.com/%s.js?v=3.1.1&type=inline&autoplay=%d', $this->getVideoId(), $autoplay),
      ],
      '#attached' => [
        'library' => [
          'video_embed_vidyard/styles',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    return sprintf("https://play.vidyard.com/%s.jpg", $this->getVideoId());
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    $parsed_url = parse_url($input);
    // Retrieve the configuration.
    $vidyard_config = \Drupal::config('video_embed_vidyard.settings');
    $host_name = $vidyard_config->get('custom_domain');
    $additional_pattern = $vidyard_config->get('additional_pattern');
    $default_pattern = 'share|embed\_select';

    if (!empty($parsed_url['host']) && !empty($parsed_url['path'])) {
      if (stripos($parsed_url['host'], 'vidyard.com') !== FALSE) {
        preg_match('@\/(?:' . $default_pattern . ')\/(?<id>[\_\-a-zA-Z0-9]+)@i', $parsed_url['path'], $matches);
        return !empty($matches['id']) ? $matches['id'] : FALSE;
      }
      elseif (stripos($parsed_url['host'], $host_name) !== FALSE) {
        $pattern = $default_pattern . '|' . Html::escape($additional_pattern);
        preg_match('@\/(?:' . $pattern . ')\/(?<id>[\_\-a-zA-Z0-9]+)@i', $parsed_url['path'], $matches);
        return !empty($matches['id']) ? $matches['id'] : FALSE;
      }
    }

    return FALSE;
  }

}
