<?php

namespace Drupal\wizenoze;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Search page entities.
 */
interface WizenozePageInterface extends ConfigEntityInterface {

  /**
   * Return the path.
   *
   * @return string
   *   The path.
   */
  public function getPath();

  /**
   * Return the clean URL configuration.
   *
   * @return bool
   *   The clean url.
   */
  public function getCleanUrl();

  /**
   * Return the search api index.
   *
   * @return string
   *   The index.
   */
  public function getIndex();

  /**
   * Return the limit per page.
   *
   * @return int
   *   The page limit.
   */
  public function getLimit();

  /**
   * Whether to show the search form above the search results.
   *
   * @return bool
   *   TRUE when search form needs to be shown.
   */
  public function showSearchForm();

  /**
   * Show all results when no search is performed.
   *
   * @return bool
   *   TRUE when having to show all results.
   */
  public function showAllResultsWhenNoSearchIsPerformed();

}
