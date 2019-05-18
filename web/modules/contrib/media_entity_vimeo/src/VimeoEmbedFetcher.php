<?php

/**
 * @file
 */

namespace Drupal\media_entity_vimeo;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Client;
use Drupal\Core\Url;


/**
 * Fetches vimeo post via oembed.
 */
class VimeoEmbedFetcher implements VimeoEmbedFetcherInterface {

  // Constant for different URL of Vimeo.
  const VIMEO_VIDEO_URL = 'https://vimeo.com';
  const VIMEO_PLAYER_URL = 'https://player.vimeo.com';
  const VIMEO_JSON_URL = 'http://vimeo.com/api/oembed.json';

  /**
   * Guzzle client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * VimeoEmbedFetcher constructor.
   *
   * @param \GuzzleHttp\Client $client
   *   A HTTP Client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   A logger factory.
   */
  public function __construct(Client $client, LoggerChannelFactoryInterface $loggerFactory) {
    $this->httpClient = $client;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchVimeoEmbed($video_url, $autoplay = 1, $loop = 1, $fullscreen = 1) {

    $url = Url::fromUri(self::VIMEO_JSON_URL, ['query' => ['url' => $video_url]])->toString();

    $response = $this->httpClient->request('GET', $url, ['headers' => ['Accept' => 'application/json']]);

    $options = [];

    if ($response->getStatusCode() == 200) {
      $data = json_decode($response->getBody(), TRUE);

      $data['embed_url'] = self::VIMEO_PLAYER_URL . $data['uri'];
      if ($autoplay == 1) {
        $options['autoplay'] = 1;
      }
      if ($loop == 1) {
        $options['loop'] = 1;
      }
      if ($fullscreen == 1) {
        $data['fullscreen'] = ' webkitallowfullscreen mozallowfullscreen allowfullscreen ';
      }

      $queryParameter = UrlHelper::buildQuery($options);
      $data['embed_url'] = self::VIMEO_PLAYER_URL . '/video/' . $data['video_id'] . '?' . $queryParameter;

      return $data;
    }
  }

}
