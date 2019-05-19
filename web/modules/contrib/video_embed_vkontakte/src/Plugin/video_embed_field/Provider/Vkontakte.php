<?php

namespace Drupal\video_embed_vkontakte\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;
use VK\Client\VKApiClient;
use VK\Exceptions\VKClientException;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * @VideoEmbedProvider(
 *   id = "vkontakte",
 *   title = @Translation("Vkontakte")
 * )
 */
class Vkontakte extends ProviderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    // @todo is it possible use autoplay?
    return [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
        'src' => $this->getVideoFromInput(),
      ],
    ];
  }

  /**
   * Get Vkontakte access token.
   *
   * @return string
   *   The access token.
   */
  private function getAccessToken() {
    return \Drupal::config('video_embed_vkontakte.settings')->get('access_token');
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    $vk = new VKApiClient();
    $input = $this->getInput();

    try {
      $response = $vk->video()->get($this->getAccessToken(), [
        'owner_id' => static::getComponent($input, 'oid'),
        'videos' => [static::getComponent($input, 'oid') . '_' . static::getComponent($input, 'id')],
      ]);

      // @todo maybe exist a better way to implement it?
      $previews = [
        'photo_800',
        'photo_320',
        'photo_130',
      ];

      foreach ($previews as $preview) {
        if (isset($response['items'][0][$preview])) {
          return $response['items'][0][$preview];
        }
      }
    }
    catch (VKClientException $exception) {}

    return '';
  }

  /**
   * Get link to a video.
   *
   * @return string
   *   The link to a video.
   */
  private function getVideoFromInput() {
    $input = $this->getInput();

    $cache = \Drupal::cache()->get('video_embed_vkontakte:' . static::getIdFromInput($input));

    if ($cache) {
      return $cache->data;
    }

    try {
      $vk = new VKApiClient();
      $response = $vk->video()->get($this->getAccessToken(), [
        'owner_id' => static::getComponent($input, 'oid'),
        'videos' => [static::getComponent($input, 'oid') . '_' . static::getComponent($input, 'id')],
      ]);

      if (isset($response['items'][0]['player']) ) {
        \Drupal::cache()->set(
          'video_embed_vkontakte:' . static::getIdFromInput($input),
          $response['items'][0]['player'],
          CacheBackendInterface::CACHE_PERMANENT
        );

        return $response['items'][0]['player'];
      }
    }
    catch (VKClientException $exception) {}

    return '';
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    $oid = static::getComponent($input, 'oid');
    $id = static::getComponent($input, 'id');

    return !empty($oid) && !empty($id) ? $oid . '_' . $id : '';
  }

  /**
   * @param $input
   *  IFrame of video.
   *
   * @return string
   *  The direct video url.
   */
  public static function getUrlFromInput($input) {
    $oid = static::getComponent($input, 'oid');
    $id = static::getComponent($input, 'id');

    if (empty($oid) && empty($id)) {
      return $input;
    }

    return 'https://vk.com/video' . $oid . '_' . $id;
  }

  /**
   * Get a component from the URL.
   *
   * @param string $input
   *   The input URL.
   * @param string $component
   *   The component from the regex to get.
   *
   * @return string
   *   The value of the match in the regex.
   */
  protected static function getComponent($input, $component) {
    preg_match('/\/\/vk\.com\/video_ext\.php\?oid=(?<oid>[-\d]*)&id=(?<id>[\d]*)&hash=(?<hash>[\da-z]{16})/', $input, $matches);

    if (isset($matches[$component])) {
      return $matches[$component];
    }

    preg_match('/https:\/\/vk\.com\/video(?<oid>[-\d]*)_(?<id>[\d]*)/', $input, $matches);

    if (isset($matches[$component])) {
      return $matches[$component];
    }

    preg_match('/https:\/\/vk\.com\/video\?z=video(?<oid>[-\d]*)_(?<id>[\d]*)/', $input, $matches);

    if (isset($matches[$component])) {
      return $matches[$component];
    }

    return '';
  }

}
