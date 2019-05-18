<?php

namespace Drupal\search_365;

use Drupal\search_365\SearchResults\SearchQuery;

/**
 * Defines an interface for performing a search.
 */
interface SearchClientInterface {

  /**
   * Performs search.
   *
   * @param \Drupal\search_365\SearchResults\SearchQuery $searchQuery
   *   Search query.
   *
   * @return \Drupal\search_365\SearchResults\ResultSet
   *   Search result set.
   *
   * @throws \Drupal\search_365\Search365Exception
   *   If an error occurs performing a search.
   */
  public function search(SearchQuery $searchQuery);

}
