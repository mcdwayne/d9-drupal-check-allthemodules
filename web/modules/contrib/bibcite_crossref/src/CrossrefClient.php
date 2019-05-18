<?php

namespace Drupal\bibcite_crossref;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;
use RenanBr\CrossRefClient as RenanBrCrossRefClient;

/**
 * Crossref client service.
 */
class CrossrefClient implements CrossrefClientInterface {

  /**
   * Crossref REST API client.
   *
   * @var \RenanBr\CrossRefClient
   */
  protected $client;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * HumanNameParser constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   *
   * @todo Type hint for $http_client should be \GuzzleHttp\ClientInterface. \RenanBr\CrossRefClient::__construct() signature should be fixed for this first.
   */
  public function __construct(Client $http_client, ConfigFactoryInterface $config_factory) {
    $this->client = new RenanBrCrossRefClient($http_client);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function lookupDoi($doi) {
    return $this->request("works/{$doi}");
  }

  /**
   * {@inheritdoc}
   */
  public function lookupDoiRaw($doi) {
    return $this->requestRaw("works/{$doi}");
  }

  /**
   * Perform a request to Crossref REST API.
   *
   * @param string $path
   *   Ex. "works/10.1037/0003-066X.59.1.29".
   * @param array $parameters
   *   Query parameters.
   *
   * @return array
   *   Response JSON decoded as array.
   */
  protected function request($path, array $parameters = []) {
    $mailto = $this->configFactory->get('bibcite_crossref.settings')->get('bibcite_crossref_mailto');
    $defaults = ($mailto) ? ['mailto' => $mailto] : [];
    $parameters = $parameters + $defaults;
    return $this->client->request($path, $parameters);
  }

  /**
   * Perform a request to Crossref REST API and get raw JSON string.
   *
   * @param string $path
   *   Ex. "works/10.1037/0003-066X.59.1.29".
   * @param array $parameters
   *   Query parameters.
   *
   * @return string
   *   Response JSON.
   */
  protected function requestRaw($path, array $parameters = []) {
    $result = $this->request($path, $parameters);
    return \GuzzleHttp\json_encode($result);
  }

}
