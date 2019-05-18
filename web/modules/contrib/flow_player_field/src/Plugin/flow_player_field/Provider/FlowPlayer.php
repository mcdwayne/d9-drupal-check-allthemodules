<?php

namespace Drupal\flow_player_field\Plugin\flow_player_field\Provider;

use Drupal\flow_player_field\ProviderPluginBase;

/**
 * A FlowPlayer provider plugin.
 *
 * @FlowPlayerProvider(
 *   id = "flowplayer",
 *   title = @Translation("Flowplayer")
 * )
 */
class FlowPlayer extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    $embed_code = [
      '#type' => 'flow_player_iframe',
      '#provider' => 'flowplayer',
      '#url' => $this->getVideoUrl(),
      '#query' => [
        'autoplay' => $autoplay,
        'start' => $this->getTimeIndex(),
        'rel' => '0',
      ],
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
      ],
    ];
    if ($language = $this->getLanguagePreference()) {
      $embed_code['#query']['cc_lang_pref'] = $language;
    }

    return $embed_code;
  }

  /**
   * {@inheritdoc}
   */
  public function renderEmbedHtml($html) {
    $embed_code = [
      '#type' => 'flow_player_html',
      '#provider' => 'flowplayer',
      '#flowplayer_html' => $html,
    ];
    return $embed_code;
  }

  /**
   * Get the time index for when the given video starts.
   *
   * @return int
   *   The time index where the video should start based on the URL.
   */
  protected function getTimeIndex() {
    preg_match('/[&\?]t=(?<timeindex>\d+)/', $this->getInput(), $matches);
    return isset($matches['timeindex']) ? $matches['timeindex'] : 0;
  }

  /**
   * Extract the language preference from the URL for use in closed captioning.
   *
   * @return string|false
   *   The language preference if one exists or FALSE if one could not be found.
   */
  protected function getLanguagePreference() {
    preg_match('/[&\?]hl=(?<language>[a-z\-]*)/', $this->getInput(), $matches);
    return isset($matches['language']) ? $matches['language'] : FALSE;
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
    return FALSE;
  }

}
