<?php

namespace Drupal\search_365;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\search_365\SearchResults\ResultSet;
use Drupal\search_365\SearchResults\SearchQuery;
use GuzzleHttp\ClientInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class SearchClient.
 *
 * @package Drupal\search_365\Service
 */
class SearchClient implements SearchClientInterface {

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * HTTP Client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The result set serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * SearchClient constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   HTTP client.
   * @param \Symfony\Component\Serializer\SerializerInterface $serializer
   *   The result set serializer.
   */
  public function __construct(ConfigFactoryInterface $configFactory, ClientInterface $httpClient, SerializerInterface $serializer) {
    $this->config = $configFactory->get('search_365.settings');
    $this->httpClient = $httpClient;
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public function search(SearchQuery $searchQuery) {
    $baseurl = $this->config->get('connection_info.baseurl');
    $collection = $this->config->get('connection_info.collection');
    $query = [];
    if ($sortBy = $searchQuery->getSortBy()) {
      $query['sortby'] = $sortBy;
    }
    try {
      $response = $this->httpClient->request('GET',
        $baseurl . '/search/' . $collection . '/sitesearch/' . $searchQuery->getQuery() . '/' . $searchQuery->getPageNum() . '/' . $searchQuery->getSize(),
        ['query' => $query]);
      /** @var \Drupal\search_365\SearchResults\ResultSet $resultSet */
      $resultSet = $this->serializer->deserialize($response->getBody(), ResultSet::class, 'json');
      return $resultSet;
    }
    catch (\Exception $e) {
      throw new Search365Exception('Error querying search 365: ' . $e->getMessage(), $e->getCode(), $e);
    }
  }

}
