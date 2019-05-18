<?php

namespace Drupal\azure_cognitive_services_api\Service;

use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;

/**
 *
 */
class Client {

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  private $configFactory;

  /**
   * Create the Azure Cognitive Services client.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   */
  public function __construct(ConfigFactory $configFactory) {
    // Get the config.
    $this->config = $configFactory->get('azure_cognitive_services_api.settings');
  }

  /**
   * @param $service
   */
  public function getClient($service) {
    $subscriptionKey = $this->config->get($service . '_subscription_key');
    $azureApiUri = 'https://' . $this->config->get($service . '_azure_region') . $this->config->get('azure_api_base_url');

    $this->guzzleClient = new GuzzleClient(
      [
        'base_uri' => $azureApiUri,
        'headers'  => [
          'Content-Type' => 'application/json',
          'Ocp-Apim-Subscription-Key' => $subscriptionKey,
        ],
      ]
    );
  }

  /**
   * @param $service
   * @param $uri
   * @param string $method
   * @param array $body
   *
   * @return bool|mixed
   */
  public function doRequest($service, $uri, $method = 'GET', $body = []) {
    self::getClient($service);

    try {
      $response = $this->guzzleClient->request(
        $method,
        $uri,
        $body
      );

      return json_decode($response->getBody(), TRUE);
    }
    catch (RequestException $e) {
      \Drupal::logger('azure_cognitive_services_api')->warning(
        "Azure Cognitive Services error code @code: @message",
        [
          '@code' => $e->getCode(),
          '@message' => $e->getMessage(),
        ]
      );

      // TODO Should this service return FALSE or throw exception... hmm.
      return FALSE;
    }
  }

}
