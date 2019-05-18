<?php

namespace Drupal\perspective;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;

/**
 * Class that define the service that analyzes the toxicity.
 */
class AnalyzeToxicityService implements ContainerFactoryPluginInterface {

  /**
   * Stores the toxicity of a text.
   *
   * @var \Drupal\perspective\AnalyzeToxicityService
   */
  protected $toxicity;

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Guzzle Http Client.
   *
   * @var GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * When the service is created, set a value for the toxicity variable.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Client $http_client) {
    $this->toxicity = 0;
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('config.factory'),
      $container->get('http_client')
    );
  }

  /**
   * Return the value of the example variable.
   */
  public function getTextToxicity($text) {
    if (!empty($text)) {
      // Loading the configuration.
      $config = $this->configFactory->get('perspective.settings');

      // Builds the API URL.
      $url = $config->get('perspective.google_api_url') . '?key=' . $config->get('perspective.google_api_key');

      // Builds the format to send to the API.
      $options = [
        'json' => [
          'comment' => [
            'text' => $text,
          ],
          'languages' => ['en'],
          'requestedAttributes' => [
            'TOXICITY' => (object) [],
          ],
        ],
        'verify' => FALSE,
      ];

      try {
        $response = $this->httpClient->request('POST', $url, $options);
        $status_code = $response->getStatusCode();

        if ($status_code == 200) {
          $toxicity = json_decode($response->getBody()->getContents(), TRUE);
        }
      }
      catch (RequestException $e) {
        watchdog_exception('perspective', $e);
      }

      $this->toxicity = ($toxicity['attributeScores']['TOXICITY']['summaryScore']['value']) * 100;
    }

    return $this->toxicity;
  }

}
