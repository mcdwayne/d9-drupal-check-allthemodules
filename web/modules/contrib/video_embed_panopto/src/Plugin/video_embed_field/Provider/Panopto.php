<?php

namespace Drupal\video_embed_panopto\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * @VideoEmbedProvider(
 *   id = "panopto",
 *   title = @Translation("Panopto")
 * )
 */
class Panopto extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    $video_id_array = explode('|', $this->getVideoId());
    $video_autoplay = $autoplay ? 'true' : 'false';
    return [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
        'src' => '//' . $video_id_array[0] . '/Panopto/Pages/Embed.aspx?id=' . $video_id_array[1] . '&v=1&autoplay=' . $video_autoplay,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    $video_id_array = explode('|', $this->getVideoId());
    return 'https://' . $video_id_array[0] . '/Panopto/PublicAPI/SessionPreviewImage?id=' . $video_id_array[1];
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    $matches = array();
    $patterns = array(
      '@.*//(.*)/Panopto/Pages/Viewer\.aspx\?id\=([^"\&]+)@i',
      '@.*//(.*)/Panopto/Pages/Embed\.aspx\?id\=([^"\&]+)@i',
    );
    foreach ($patterns as $pattern) {
      preg_match($pattern, $input, $matches);
      if (!empty($matches[1]) && !empty($matches[2])) {
        return $matches[1] . '|' . $matches[2];
      }
    }
    return FALSE;
  }

}
