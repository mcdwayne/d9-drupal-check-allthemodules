<?php

namespace Drupal\real_estate_rets;

/**
 * Fetches project information from RETS server.
 */
interface RetsFetcherInterface {

  /**
   * Retrieves a data from RETS server.
   *
   * @param array $query
   *   The array of query information.
   *
   * @return array
   *   The result of query to RETS server. Empty string upon failure.
   */
  public function fetchRetsData(array $query);

}
