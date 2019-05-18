<?php

namespace Drupal\real_estate_rets;

/**
 * Manages rets information.
 */
interface RetsManagerInterface {

  /**
   * Fetches an array of rets data.
   *
   * @return array
   *   Returns update rets data.
   */
  public function refreshRetsData();

  /**
   * Processes a step in batch for fetching RETS data.
   *
   * In example is used result of query, but we use queue items instead.
   * https://api.drupal.org/api/drupal/core%21includes%21form.inc/group/batch/8.4.x.
   *
   * @param array $context
   *   Reference to an array used for Batch API storage.
   */
  public function fetchDataBatch(array &$context);

}
