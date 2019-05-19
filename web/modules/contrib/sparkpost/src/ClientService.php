<?php

namespace Drupal\sparkpost;

use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\Client;
use SparkPost\SparkPost;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;

/**
 * Class ClientService.
 *
 * @package Drupal\sparkpost
 */
class ClientService implements ClientServiceInterface {

  /**
   * Regex for parsing email.
   */
  const EMAIL_REGEX = '/^\s*(.+?)\s*<\s*([^>]+)\s*>$/';

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Constructor.
   */
  public function __construct(Client $client, ConfigFactory $configFactory) {
    $this->client = $client;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function getClient() {
    $config = $this->configFactory->get('sparkpost.settings');
    $httpClient = new GuzzleAdapter($this->client);
    return new SparkPost($httpClient, ['key' => $config->get('api_key')]);
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage(array $message) {
    $client = $this->getClient();
    try {
      $promise = $client->transmissions->post($message);
      $response = $promise->wait();
      return $response->getBody();
    }
    catch (\Exception $e) {
      \Drupal::logger('sparkpost')->error($e->getMessage());
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function sendRequest($endpoint, array $data, $method = 'GET') {
    $client = $this->getClient();
    $promise = $client->request($method, $endpoint, $data);
    $response = $promise->wait();
    return $response->getBody();
  }

}
