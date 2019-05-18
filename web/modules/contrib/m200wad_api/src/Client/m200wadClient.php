<?php

namespace Drupal\m200wad_api\Client;

use Drupal\Core\Config\ConfigFactory;
use Drupal\m200wad_api\m200wadClientInterface;
use \GuzzleHttp\ClientInterface;
use \GuzzleHttp\Exception\RequestException;

class m200wadClient implements m200wadClientInterface {

  /**
   * An http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * A configuration instance.
   *
   * @var \Drupal\Core\Config\ConfigInterface;
   */
  protected $config;

  /**
   * 200 Words a day API Token.
   *
   * @var string
   */
  protected $token;

  /**
   * 200 Words a day Base URI.
   *
   * @var string
   */
  protected $base_uri;

  /**
   * Constructor.
   */
  public function __construct(ClientInterface $http_client, ConfigFactory $config_factory) {
    $this->httpClient = $http_client;
    $config = $config_factory->get('m200wad_api.settings');
    $this->token = $config->get('token');
    $this->base_uri = $config->get('base_uri');
  }

  /**
   * { @inheritdoc }
   */
  public function connect($method, $endpoint, $query, $body) {
    try {
      $response = $this->httpClient->{$method}(
        $this->base_uri . $endpoint,
        $this->buildOptions($query, $body)
      );
    }
    catch (RequestException $exception) {
      drupal_set_message(t('Failed to complete 200 Words a day Task "%error"', ['%error' => $exception->getMessage()]), 'error');

      \Drupal::logger('m200wad_api')->error('Failed to complete 200 Words a day Task "%error"', ['%error' => $exception->getMessage()]);
      return FALSE;
    }

    $headers = $response->getHeaders();
    \Drupal::logger('m200wad_api')->notice('Posted to 200wad with headers:  "%error"', ['%error' => print_r($response->getHeaders(), 1)]);
    \Drupal::logger('m200wad_api')->notice('Posted to 200wad with body:  "%error"', ['%error' => print_r($response->getBody(), 1)]);
    \Drupal::logger('m200wad_api')->notice('Posted to 200wad with content:  "%error"', ['%error' => print_r($response->getBody->getContents(), 1)]);
    return $response->getBody()->getContents();
  }

  /**
   * Build options for the client.
   */
  private function buildOptions($query, $body) {
    $options = [];
    if ($body) {
      $options['form_params'] = $body;
    }
    if ($query) {
      $options['query'] = $query;
    } else {
      $options['query'] = [];
    }

    $options['query']['api_key'] = $this->token;
    return $options;
  }
}
