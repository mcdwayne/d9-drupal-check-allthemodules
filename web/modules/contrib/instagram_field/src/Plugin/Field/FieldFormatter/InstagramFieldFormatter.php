<?php

namespace Drupal\instagram_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Serialization\Json;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;

/**
 * Plugin implementation of the 'instagramfield_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "instagramfield_formatter",
 *   label = @Translation("Instagram recent"),
 *   field_types = {
 *     "instagramfield"
 *   }
 * )
 */
class InstagramFieldFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The variable containing the conditions configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * The variable containing the http client.
   *
   * @var \GuzzleHttp\Client
   */
  private $httpClient;

  /**
   * The variable containing the logging.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  private $logger;

  const THUMBS_DIRECTORY = 'public://instagram_thumbnails';

  /**
   * Dependency injection through the constructor.
   *
   * @param string $plugin_id
   *   The plugin_id.
   * @param mixed $plugin_definition
   *   The plugin_definition.
   * @param mixed $field_definition
   *   The field_definition.
   * @param array $settings
   *   The settings.
   * @param string $label
   *   The label.
   * @param string $view_mode
   *   The view_mode.
   * @param array $third_party_settings
   *   The third_party_settings.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config service.
   * @param \GuzzleHttp\Client $httpClient
   *   The http client service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger service.
   */
  public function __construct($plugin_id,
  $plugin_definition,
  $field_definition,
  array $settings,
  $label,
  $view_mode,
  array $third_party_settings,
  ConfigFactoryInterface $config,
  Client $httpClient,
  LoggerChannelFactoryInterface $logger
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->config = $config->getEditable('config.instagram_field');
    $this->httpClient = $httpClient;
    $this->logger = $logger;
  }

  /**
   * Dependency injection create.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id,
    $plugin_definition,
    $configuration['field_definition'],
    $configuration['settings'],
    $configuration['label'],
    $configuration['view_mode'],
    $configuration['third_party_settings'],
    $container->get('config.factory'),
    $container->get('http_client'),
    $container->get('logger.factory'));
  }

  /**
   * Get recent posts from instagram.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $element['#cache'] = [
      'max-age' => $this->config->get('cachetime') * 60,
    ];
    if ($this->config->get('accesstoken') === '') {
      $err_msg = $this->t("instagramautherror: No access token.");
      $this->logger->get('instagram_field')->error($err_msg);
      return $element;
    }
    try {
      $request = $this->httpRequest('GET',
        'https://api.instagram.com/v1/users/self/media/recent/', [
          'query' => [
            'access_token' => $this->config->get('accesstoken'),
            'count' => count($items),
          ],
        ]
      );
    }
    catch (RequestException $e) {
      $this->logger->get('instagram_field')->error($e->getMessage());
      return $element;
    }
    $result = json::decode($request->getBody());
    foreach ($result['data'] as $key => $value) {
      if (isset($items[$key])) {
        $this->loadImage($value['id'], $value['images'][$this->config
          ->get('imageresolution')]['url']);
        $items[$key]->setValue([
          'instagramid' => $value['id'],
          'instagramlink' => $value['link'],
        ], TRUE);
        $items[$key]->getEntity()->save();
      }
    }

    $element = [];
    foreach ($items as $delta => $item) {
      if (strlen($items[$delta]->getValue()['instagramid']) > 1) {
        $element[$delta] = [
          '#type' => 'html_tag',
          '#tag' => 'A',
          '#attributes' => [
            'href' => $items[$delta]->getValue()['instagramlink'],
          ],
          'content' => [
            'img' => [
              '#type' => 'html_tag',
              '#tag' => 'img',
              '#attributes' => [
                'src' => file_create_url(self::THUMBS_DIRECTORY .
                  '/' . $items[$delta]->getValue()['instagramid']
                  . '.jpg'),
              ],
            ],
          ],
          '#cache' => [
            'max-age' => $this->config->get('cachetime') * 60,
          ],
        ];
      }
    }
    return $element;
  }

  /**
   * Load image from disk or download it from instagram.
   */
  private function loadImage($imageid, $url) {
    $directory = self::THUMBS_DIRECTORY;
    $local_uri = $directory . '/' . $imageid . '.jpg';
    if (!file_exists($local_uri)) {
      file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
      try {
        $thumbnail = $this->httpClient->request('GET', $url);
        file_unmanaged_save_data((string) $thumbnail->getBody(),
          $local_uri);
      }
      catch (\Exception $e) {

      }
    }
  }

  /**
   * HttpClient request method enable test mock.
   */
  private function httpRequest($method, $uri, $options) {
    if (drupal_valid_test_ua()) {
      return new Response(200, [], file_get_contents(dirname(__FILE__) . '/../../../../tests/src/Functional/Mocks/instagram_users_self_media_recent.json'));
    }
    else {
      return $this->httpClient->request($method, $uri, $options);
    }
  }

}
