<?php

namespace Drupal\openimmo;

/**
 * Fetches project information from OpenImmo server.
 */
interface OpenImmoFetcherInterface {

  /**
   * Retrieves a data from OpenImmo server.
   *
   * @param array $query
   *   The array of query information.
   *
   * @return array
   *   The result of query to OpenImmo server. Empty string upon failure.
   */
  public function fetchOpenImmoData(array $query);

}
