<?php

namespace Drupal\search_365_test;

use Drupal\search_365\SearchClient;
use Drupal\search_365\SearchResults\ResultSet;
use Drupal\search_365\SearchResults\SearchQuery;
use Drupal\search_365\Serializer\ResultSetSerializerFactory;

/**
 * Defines a test only search implementation.
 */
class MockSearchClient extends SearchClient {

  /**
   * {@inheritdoc}
   */
  public function search(SearchQuery $searchSearchQuery) {

    if ($searchSearchQuery->getQuery() === 'nill') {
      $resultSet = ResultSet::create();
      return $resultSet;
    }

    $serializer = ResultSetSerializerFactory::create();
    $jsonData = file_get_contents(__DIR__ . '/../../../fixtures/response.json');
    /** @var \Drupal\search_365\SearchResults\ResultSet $resultSet */
    $resultSet = $serializer->deserialize($jsonData, ResultSet::class, 'json');
    return $resultSet;

  }

}
