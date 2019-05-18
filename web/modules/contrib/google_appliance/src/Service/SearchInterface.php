<?php

namespace Drupal\google_appliance\Service;

use Drupal\google_appliance\SearchResults\SearchQuery;

/**
 * Defines an interface for performing a GSA search.
 */
interface SearchInterface {

  /**
   * Performs search.
   *
   * @param \Drupal\google_appliance\SearchResults\SearchQuery $query
   *   Search query.
   *
   * @return \Drupal\google_appliance\SearchResults\ResultSet
   *   Search result set.
   */
  public function search(SearchQuery $query);

}
