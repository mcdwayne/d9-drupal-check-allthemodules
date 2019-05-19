<?php

namespace Drupal\video_embed_twentythree\Plugin\video_embed_field\Provider;

use Drupal\video_embed_field\ProviderPluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * A TwentyThree provider plugin.
 *
 * @VideoEmbedProvider(
 *   id = "twentythree",
 *   title = @Translation("TwentyThree")
 * )
 */
class TwentyThree extends ProviderPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The http client service.
   *
   * @var GuzzleHttp\Client
   *
   * @see GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the TwentyThree object.
   *
   * @param array $configuration
   *   The factory for configuration objects.
   * @param $plugin_id
   *   The plugin id.
   * @param $plugin_definition
   *   The plugin definition.
   * @param GuzzleHttp\Client $http_client
   *   The http client service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory services.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Client $http_client, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $http_client);
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
  }

  /**
   * Creates the TwentyThree object.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The factory for configuration objects.
   * @param array $configuration
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $plugin_id
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $plugin_definition
   *   The factory for configuration objects.
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
    $config = $this->configFactory->get('video_embed_twentythree.settings');
    $domain = parse_url($this->getInput(), PHP_URL_HOST);

    // Auto mute when video is auto-playing to avoid video being blocked by
    // some browsers including Chrome and Firefox.
    if ($autoplay && $config->get('automute_autoplay_videos')) {
      $automute = TRUE;
    }
    else {
      $automute = FALSE;
    }

    $embed_code = [
      '#type' => 'video_embed_iframe',
      '#provider' => 'twentythree',
      '#url' => 'https://' . $domain . '/v.ihtml/player.html',
      '#query' => [
        'photo_id' => $this->getVideoId(),
        'autoPlay' => $autoplay,
        'autoMute' => $automute
      ],
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen'
      ],
    ];

    // Add query parameters from embed URL if enabled.
    if ($config->get('enable_query_parameters') && !empty($config->get('allowed_query_parameters'))) {
      $query_parameters = $this->getEmbedQueryParameters();

      if (!empty($query_parameters)) {
        $embed_code['#query'] = array_merge($query_parameters, $embed_code['#query']);
      }
    }

    return $embed_code;
  }

  /**
   * Get the TwentyThree oembed data.
   *
   * @return array|FALSE
   *   An array of data from the oembed endpoint.
   */
  protected function oEmbedData() {
    $url = $this->getInput();
    $url_parts = parse_url($url);
    $query = [
      'url' => $url,
      'format' => 'json',
    ];
    $oembed_url = $url_parts['scheme'] . '://' . $url_parts['host'] . '/oembed?';

    try {
      $request = $this->httpClient->get($oembed_url, ['query' => $query]);
      if ($request->getStatusCode() == 200) {
        return json_decode($request->getBody()->getContents());
      }
    }
    catch (RequestException $e) {
      return FALSE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->oEmbedData()->title;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteThumbnailUrl() {
    return $this->oEmbedData()->thumbnail_url;
  }

  /**
   * {@inheritdoc}
   */
  public static function getIdFromInput($input) {
    $config = \Drupal::config('video_embed_twentythree.settings');
    $domains = str_replace("\n", '|', $config->get('video_domains'));
    $url = urldecode($input);

    $matches1 = $matches2 = [];
    preg_match("/^https?:\/\/({$domains})\/video\/(?<id>[a-z0-9]+).*$/", $url, $matches1);
    preg_match("/^https?:\/\/({$domains})\/v.ihtml\/player.html\?.*photo_id=(?<id>[0-9]+).*$/", $url, $matches2);

    if ($matches1 && !empty($matches1['id'])) {
      return (int) $matches1['id'];
    }
    else if ($matches2 && !empty($matches2['id'])) {
      return (int) $matches2['id'];
    }
    else {
      return FALSE;
    }
  }

  /**
   * Get the start time from the URL.
   *
   * @return string|FALSE
   *   A start parameter to pass to the frame or FALSE if none is found.
   */
  protected function getStartTime() {
    preg_match('/\?.*start=(?<start>(\d+)s)$/', $this->input, $matches);
    return isset($matches['start']) ? $matches['start'] : FALSE;
  }

  /**
   * Get query parameters from the embed URL and filter them based on allowed
   * parameters.
   *
   * @return array
   *   List with filtered query parameters.
   */
  protected function getEmbedQueryParameters() {
    $url = $this->getInput();
    $config = $this->configFactory->get('video_embed_twentythree.settings');
    $allowed_parts = $config->get('allowed_query_parameters');
    $result = [];

    // Parse query string.
    $query_string = parse_url($url, PHP_URL_QUERY);
    parse_str($query_string, $query_parts);

    // Filter unwanted query parameters.
    foreach ($allowed_parts as $key) {
      if (isset($query_parts[$key])) {
        $result[$key] = $query_parts[$key];
      }
    }

    return $result;
  }

  /**
   * Get token from the URL if exist.
   *
   * Private TwentyThree video sites used URL tokens when videos are shared
   * public.
   *
   * @return string|FALSE
   *   The token parameter to include in the video embed or FALSE if none is found.
   */
  protected function getToken($input) {
    $query_string = parse_url($input, PHP_URL_QUERY);
    parse_str($query_string, $query_string);
    return !empty($query_string['token']) ? $query_string['token'] : FALSE;
  }


}
