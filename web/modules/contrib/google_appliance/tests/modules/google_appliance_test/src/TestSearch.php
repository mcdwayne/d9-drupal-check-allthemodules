<?php

namespace Drupal\google_appliance_test;

use Drupal\google_appliance\SearchResults\ResultSet;
use Drupal\google_appliance\SearchResults\Result;
use Drupal\google_appliance\SearchResults\SearchQuery;
use Drupal\google_appliance\SearchResults\Synonym;
use Drupal\google_appliance\Service\Search;

/**
 * Defines a test only search implementation.
 */
class TestSearch extends Search {
  const TOTAL = 100;

  /**
   * {@inheritdoc}
   */
  public function search(SearchQuery $searchQuery) {
    $config = $this->configFactory->get('google_appliance.settings');
    $perPage = $config->get('display_settings.results_per_page');
    $result = new ResultSet();
    $result->setTotal(self::TOTAL)->setLastResult(20);
    foreach (range(1, $perPage) as $item) {
      $result->addResult(new Result('http://example.com', 'http://example.com', sprintf('Result %d', $item), sprintf('this is a snippet from item %d', $item), date('Y-m-d'), 'text/html'));
    }
    if ($searchQuery->getSearchQuery() === 'unicorns') {
      $result->addSynonym(new Synonym('Donkeys', 'donkeys'));
    }
    return $result
      ->setSearchTitle($config->get('display_settings.search_title'))
      ->setQuery($searchQuery)
      ->setResultsPerPage($perPage);
  }

}
