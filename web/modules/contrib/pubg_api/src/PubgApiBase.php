<?php

namespace Drupal\pubg_api;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use GuzzleHttp\Client;

/**
 * Pubg Api Base.
 */
class PubgApiBase implements PubgApiBaseInterface {

  /**
   * PUBG API Base URL.
   *
   * @var string
   */
  protected $apiBaseUrl;

  /**
   * PUBG API key.
   *
   * @var string
   */
  protected $apiKey;

  /**
   * Guzzle Http Client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * PubgApiBase constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   *   A guzzle http client.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The Messenger service.
   */
  public function __construct(Client $http_client, ConfigFactory $config_factory, MessengerInterface $messenger) {
    $this->apiBaseUrl = Data::PUBG_API_BASE_URL;
    $this->apiKey = $config_factory
      ->getEditable('pubg_api.settings')
      ->get('pubg_apikey');
    $this->httpClient = $http_client;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse(string $shard, string $api_endpoint, array $endpoint_options = []) {
    if (empty($this->apiKey)) {
      $this->messenger->addError("You must set your API Key.");
      return;
    }

    if (!in_array($shard, Data::PUBG_SHARDS)) {
      $this->messenger->addError("Given shard is invalid.");
      return;
    }

    $api_url = "{$this->apiBaseUrl}/{$shard}/{$api_endpoint}";
    $url = Url::fromUri($api_url, $endpoint_options)->toString();

    $req_options = [
      'verify' => FALSE,
      'headers' => [
        'Authorization' => "Bearer {$this->apiKey}",
        'Accept' => 'application/vnd.api+json',
      ],
    ];

    try {
      $request = $this->httpClient->request('GET', $url, $req_options);
      $response = $request->getBody()->getContents();
      $response = json_decode($response, TRUE);

      return $response;
    }
    catch (\Exception $e) {
      return $e->getMessage();
    }
  }

}
