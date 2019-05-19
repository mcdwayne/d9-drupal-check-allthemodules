<?php

namespace Drupal\video_embed_damdy\Plugin\video_embed_field\Provider;

/**
 * @file
 * Contains \Drupal\video_embed_damdy\Plugin\video_embed_field\Provider\Damdy.
 */

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * {@inheritdoc}
 *
 * @VideoEmbedProvider(
 *   id = "damdy",
 *   title = @Translation("Damdy")
 * )
 */
class Damdy extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    $config = \Drupal::configFactory()->getEditable('video_embed_damdy.settings');

    return [
      '#theme' => 'video_embed_damdy',
      '#media_id' => $this->getVideoId(),
      '#publisher_id' => !empty($config->get('damdy_publisher_id')) ? $config->get('damdy_publisher_id') : '',
      '#medias_xml_url' => !empty($config->get('damdy_media_xml_url')) ? $config->get('damdy_media_xml_url') : '',
      '#player_params_url' => !empty($config->get('damdy_param_url')) ? $config->get('damdy_param_url') : '',
      '#player_guid' => !empty($config->get('damdy_guid')) ? $config->get('damdy_guid') : '',
      '#autostart' => ($autoplay) ? '1' : '0',
      '#attached' => [
        'library' => [
          'video_embed_damdy/video-embed-damdy',
        ],
        'drupalSettings' => ['damdy_config_js' => !empty($config->get('damdy_config_js')) ? $config->get('damdy_config_js') : ''],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    $config = \Drupal::configFactory()->getEditable('video_embed_damdy.settings');

    $xmlUrl = !empty($config->get('damdy_media_xml_url')) ? $config->get('damdy_media_xml_url') : '';
    if (!empty($xmlUrl)) {
      $xmlUrl .= '&byMediaId=' . $this->getVideoId();
      $infos = simplexml_load_file($xmlUrl);
      if (!empty($infos->medias->media) && count($infos->medias->media->photo_url) > 0) {
        $damdyPoster = $infos->medias->media->photo_url[0]->__toString();
      }
    }
    else {
      $damdyPoster = sprintf('https://damdy.com/wp-content/uploads/2017/08/DAMDY_LOGO_Blanc.png');
    }

    return $damdyPoster;
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    preg_match('/damdy\/(?P<id>\d+)/', $input, $matches);
    return isset($matches['id']) ? $matches['id'] : FALSE;
  }

}
