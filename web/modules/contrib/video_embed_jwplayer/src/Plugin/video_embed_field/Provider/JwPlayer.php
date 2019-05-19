<?php

namespace Drupal\video_embed_jwplayer\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * A JW Player provider plugin.
 *
 * @VideoEmbedProvider(
 *  id = "jwplayer",
 *  title = @Translation("JW Player")
 * )
 */
class JwPlayer extends ProviderPluginBase {

  /**
   * The default width in pixels of the remote thumbnail to download.
   *
   * @var int
   */
  const REMOTE_THUMBNAIL_WIDTH = 720;

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    return [
      '#type' => 'video_embed_iframe',
      '#provider' => 'jwplayer',
      '#url' => sprintf('//content.jwplatform.com/players/%s.html', $this->getVideoId()),
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    if ($media_id = $this->getJwPlayerMediaId()) {
      return sprintf("https://cdn.jwplayer.com/thumbs/%s-%s.jpg", $media_id, $this->getRemoteThumbnailWidth());
    }
  }

  /**
   * Determine the remote thumbnail width to download.
   *
   * See https://developer.jwplayer.com/jw-platform/docs/delivery-api-reference/#!/poster_images/get_thumbs_media_id_thumb_width_jpg.
   *
   * @return int
   *   The width in pixels.
   */
  public function getRemoteThumbnailWidth() {
    return static::REMOTE_THUMBNAIL_WIDTH;
  }

  /**
   * Extracts the media id from the provided combined id.
   *
   * This is an 8 character string that can be found on the videos overview and
   * details pages in the JW Platform dashboard.
   * Example "nPripu9l".
   *
   * @return string|null
   *   The media id.
   */
  public function getJwPlayerMediaId() {
    if ($embed_id = $this->getVideoId()) {
      @list($media_id, $player_id) = explode('-', $embed_id);
      return $media_id ? $media_id : NULL;
    }
  }

  /**
   * Extracts the player id from the provided combined id.
   *
   * This is an 8 character string that can be found in the players overview
   * and details pages in the JW Platform dashboard.
   * Example: "ALJ3XQCI".
   *
   * @return string
   *   The player id.
   */
  public function getJwPlayerPlayerId() {
    if ($embed_id = $this->getVideoId()) {
      @list($media_id, $player_id) = explode('-', $embed_id);
      return $player_id ? $player_id : NULL;
    }
  }

  /**
   * {@inheritdoc}
   *
   * Parses urls in the format of:
   * - "content.jwplatform.com/players/MEDIAID-PLAYERID.js".
   * - "content.jwplatform.com/players/MEDIAID-PLAYERID.html".
   * - "https://content.jwplatform.com/previews/MEDIAID-PLAYERID".
   * - Any url with "//SUBDOMAIN.jwplatform.com/SOMETHING/MEDIAID-PLAYERID".
   */
  public static function getIdFromInput($input) {
    if (preg_match('@\/\/\w+\.jwplatform\.com\/[^\/]+\/(?<id>[\_\-a-zA-Z0-9]+)@i', $input, $matches)) {
      return !empty($matches['id']) ? $matches['id'] : FALSE;
    }

    return FALSE;
  }

}
