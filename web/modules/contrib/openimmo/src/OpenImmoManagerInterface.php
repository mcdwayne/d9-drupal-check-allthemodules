<?php

namespace Drupal\openimmo;

/**
 * Manages openimmo information.
 */
interface OpenImmoManagerInterface {

  /**
   * Fetches an array of openimmo data.
   *
   * @return array
   *   Returns update openimmo data.
   */
  public function refreshOpenImmoData();

  /**
   * Processes a step in batch for fetching OpenImmo data.
   *
   * In example is used result of query, but we use queue items instead.
   * https://api.drupal.org/api/drupal/core%21includes%21form.inc/group/batch/8.4.x.
   *
   * @param array $context
   *   Reference to an array used for Batch API storage.
   */
  public function fetchDataBatch(array &$context);

}
