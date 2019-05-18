<?php

namespace Drupal\searchcloud_block\Services;

interface SearchCloudServiceProviderInterface {

  /**
   * Get the term.
   */
  public function getTerm($term = FALSE);

  /**
   * Get the term.
   */
  public function getTermFromUrl($position = 0);

  /**
   * Get results from the DB.
   */
  public function getResult($all = FALSE, $key = FALSE, $amount = FALSE, $order = FALSE, $raw = FALSE);

  /**
   * Generate the result build query.
   */
  public function getResultBuildQuery(&$query, $key, $all, $amount, $order);

  /**
   * Sanitize paths.
   */
  public function sanitizePaths($paths);

  /**
   * Set path results.
   */
  public function setPathResult($key, $ref);

  /**
   * Set path results.
   */
  public function checkKeys($key);

}
