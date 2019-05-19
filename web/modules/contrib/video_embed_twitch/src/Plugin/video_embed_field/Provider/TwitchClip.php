<?php

namespace Drupal\video_embed_twitch\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;

/**
 * Provides a Twitch Clip plugin for video_embed_field.
 *
 * @VideoEmbedProvider(
 *   id = "twitch_clip",
 *   title = @Translation("Twitch Clip")
 * )
 */
class TwitchClip extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    return [
      '#type' => 'video_embed_iframe',
      '#provider' => 'twitch_clip',
      '#url' => sprintf('https://clips.twitch.tv/embed?clip=%s', $this->getVideoId()),
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'scrolling' => 'no',
        'allowfullscreen' => 'allowfullscreen',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    $image_url = $this->twitchGetThumbnailUrl($this->getVideoId());
    if ($image_url) {
      return $image_url;
    }
    return FALSE;
  }

  /**
   * Helper function to get a thumbnail for Twitch Clip.
   *
   * Crawls the clip url looking for og:image meta element.
   *
   * @param string $id
   *   The Twitch clip slug.
   *
   * @return bool|array
   *   Returns FALSE when no image found or array containing image url.
   */
  public static function twitchGetThumbnailUrl($id) {
    $matches = [];
    $url = 'https://clips.twitch.tv/' . $id;
    $client = \Drupal::httpClient();

    $response = $client->get($url);
    $response_body = $response->getBody()->getContents();
    if (!empty($response_body)) {
      // Get image from Open Graph metatag:
      // <meta property='og:image' content='.
      preg_match("/<meta property='og:image' content='(.*?)'>/", $response_body, $matches);
    }
    else {
      return FALSE;
    }
    if (!empty($matches[1])) {
      return $matches[1];
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    preg_match('/^https?:\/\/clips\.twitch\.tv\/(?<slug>[a-zA-Z0-9_-]*)?$/', $input, $matches);
    return isset($matches['slug']) ? $matches['slug'] : FALSE;
  }

}
