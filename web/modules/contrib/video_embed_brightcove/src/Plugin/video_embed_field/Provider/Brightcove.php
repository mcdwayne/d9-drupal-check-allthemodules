<?php

namespace Drupal\video_embed_brightcove\Plugin\video_embed_field\Provider;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\video_embed_field\ProviderPluginBase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Brightcove provider plugin.
 *
 * @VideoEmbedProvider(
 *   id = "brightcove",
 *   title = @Translation("Brightcove")
 * )
 */
class Brightcove extends ProviderPluginBase {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Create a plugin with the given input.
   *
   * @param string $configuration
   *   The configuration of the plugin.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   An HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   *
   * @throws \Exception
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, ClientInterface $http_client, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $http_client);

    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function renderEmbedCode($width, $height, $autoplay) {
    // Get configured autoplay player name if needed.
    if ($autoplay) {
      $config = $this->configFactory->get('video_embed_brightcove.settings');
      $player_name = $config->get('autoplay_player');
    }
    // Use player name from the input URL if autoplay player is not set.
    if (empty($player_name)) {
      $player_name = $this->getPlayerName();
    }
    return [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
        'src' => sprintf('https://players.brightcove.net/%d/%s_default/index.html?videoId=%d', $this->getPlayerId(), $player_name, $this->getVideoId(), $autoplay),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    // Get configured credentials.
    $config = $this->configFactory->get('video_embed_brightcove.settings');
    $client_id = $config->get('client_id');
    $client_secret = $config->get('client_secret');

    // Skip if credentials are not configured.
    if (empty($client_id) || empty($client_secret)) {
      throw new \Exception('Brightcove API credentials are not set.');
    }

    // Process authentication to get access token.
    $auth_uri = 'https://oauth.brightcove.com/v4/access_token';
    $auth_string = base64_encode($client_id . ':' . $client_secret);
    $auth_options = [
      'headers' => [
        'Authorization' => 'Basic ' . $auth_string,
        'Content-Type' => 'application/x-www-form-urlencoded',
      ],
      'body' => 'grant_type=client_credentials',
    ];
    $auth = $this->httpClient->request('POST', $auth_uri, $auth_options);

    // Skip if authentication was not successful.
    if ($auth->getStatusCode() !== 200) {
      throw new \Exception('Brightcove API authentication failed.');
    }
    $auth_json = json_decode($auth->getBody()->getContents());

    // Process request to get video images.
    if (!empty($auth_json->access_token)) {
      $video_uri = 'https://cms.api.brightcove.com/v1/accounts/'
        . $this->getPlayerId() . '/videos/' . $this->getVideoId() . '/images';
      $video_options = [
        'headers' => [
          'Authorization' => 'Bearer ' . $auth_json->access_token,
          'Content-Type' => 'application/json',
        ],
      ];
      $video = $this->httpClient->request('GET', $video_uri, $video_options);

      // Skip if request was not successful.
      if ($video->getStatusCode() !== 200) {
        throw new \Exception('Brightcove API video request failed.');
      }
      $video_json = json_decode($video->getBody()->getContents());

      // Get poster image URL if available.
      if (!empty($video_json->poster->src)) {
        return $video_json->poster->src;
      }
    }

    // Throw exception if something went wrong.
    throw new \Exception('Brightcove API video thumbnail unavailable.');
  }

  /**
   * Get the player ID from the input URL.
   *
   * @return string
   *   The video player ID.
   */
  protected function getPlayerId() {
    return static::getUrlMetadata($this->getInput(), 'player');
  }

  /**
   * Get the player name from the input URL.
   *
   * @return string
   *   The video player name.
   */
  protected function getPlayerName() {
    return static::getUrlMetadata($this->getInput(), 'player_name');
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    return static::getUrlMetadata($input, 'id');
  }

  /**
   * Extract metadata from the input URL.
   *
   * @param string $input
   *   Input a user would enter into a video field.
   * @param string $metadata
   *   The metadata matching the regex capture group to get from the URL.
   *
   * @return string|bool
   *   The metadata or FALSE on failure.
   */
  protected static function getUrlMetadata($input, $metadata) {
    preg_match('/^https?:\/\/players\.brightcove\.net\/(?<player>[0-9]*)\/(?<player_name>[\S]*)_default\/index\.html\?videoId\=(?<id>[0-9]*)?$/', $input, $matches);
    return isset($matches[$metadata]) ? $matches[$metadata] : FALSE;
  }

}
