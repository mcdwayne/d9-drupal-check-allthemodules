<?php

namespace Drupal\demandbase_api;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\key\KeyRepositoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class DemandbaseApiConnector.
 */
class DemandbaseApiConnector {
  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;


  protected $key;
  /**
   * Constructs a new DemandbaseApiConnector object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, KeyRepositoryInterface $key_repository, Client $http_client, RequestStack $request, LoggerChannelFactoryInterface $logger_factory) {
    $this->config = $config_factory->get('demandbase_api.settings');
    $this->httpClient = $http_client;
    $this->logger = $logger_factory;
    if($this->config->get('api_key')) {
      $this->key = $key_repository->getKey($this->config->get('api_key'));
    }
  }

  /**
   * Function to fetch data from Demandbase's Company API. Returns a JSON-decoded
   * object if a successful response is received, NULL otherwise.
   *
   * @return mixed|null
   */
  public function getCompanyData($ip = null) {
    $options = [
      'query' => [
        'key'   => $this->key->getKeyValue(),
      ],
    ];
    if($ip) {
      $options['query']['query'] = $ip;
    }
//    $response = $this->httpClient->get('https://api.demandbase.com/api/v2/ip.json', $options);
    try {
      $response = $this->httpClient->get('https://api.demandbase.com/api/v2/ip.json', $options);
      $status = $response->getStatusCode();
      if ($status == '200') {
        $data = $response->getBody()->getContents();
        return json_decode($data);
      }
      else {
//        $this->logger->error('An error occurred while fetching data.');
        return NULL;
      }
    } catch (RequestException $exception) {
//      $this->logger->error($exception->getMessage());
      return NULL;
    }
  }

}
