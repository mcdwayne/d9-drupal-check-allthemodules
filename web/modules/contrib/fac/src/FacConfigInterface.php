<?php

namespace Drupal\fac;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Fast Autocomplete config entity.
 */
interface FacConfigInterface extends ConfigEntityInterface {

  /**
   * Gets the Search Plugin ID.
   *
   * @return string
   *   The Search Plugin ID.
   */
  public function getSearchPluginId();

  /**
   * Gets the Search Plugin configuration.
   *
   * @return array
   *   The Search Plugin configuration.
   */
  public function getSearchPluginConfig();

  /**
   * Gets the input selectors.
   *
   * @return string
   *   The input selectors.
   */
  public function getInputSelectors();

  /**
   * Gets the number of results.
   *
   * @return int
   *   The number of results.
   */
  public function getNumberOfResults();

  /**
   * Gets the empty result.
   *
   * @return int
   *   The empty result.
   */
  public function getEmptyResult();

  /**
   * Gets the view modes.
   *
   * @vreturn array
   *   The view modes.
   */
  public function getViewModes();

  /**
   * Gets the minimum key length.
   *
   * @return int
   *   The minimum key length.
   */
  public function getKeyMinLength();

  /**
   * Gets the maximum key length.
   *
   * @return int
   *   The maximum key length.
   */
  public function getKeyMaxLength();

  /**
   * Returns whether or not to show the all results link.
   *
   * @return bool
   *   TRUE when the show all results link must be shown, FALSE otherwise.
   */
  public function showAllResultsLink();

  /**
   * Gets the all results link threshold.
   *
   * @return int
   *   The all results link threshold.
   */
  public function getAllResultsLinkThreshold();

  /**
   * Gets the breakpoint.
   *
   * @return int
   *   The breakpoint.
   */
  public function getBreakpoint();

  /**
   * Gets the result location.
   *
   * @return string
   *   The result location.
   */
  public function getResultLocation();

  /**
   * Returns whether or not to use highlighting.
   *
   * @return bool
   *   TRUE when highlighting is enabled, FALSE otherwise.
   */
  public function highlightingEnabled();

  /**
   * Returns whether or not to perform search anonymous.
   *
   * @return bool
   *   TRUE when to search anonymous, FALSE otherwise.
   */
  public function anonymousSearch();

  /**
   * Returns whether or not to clean up the files.
   *
   * @return bool
   *   TRUE when the files must be cleaned up, FALSE otherwise.
   */
  public function cleanUpFiles();

  /**
   * Gets the files expiry time.
   *
   * @return int
   *   The files expiry time.
   */
  public function getFilesExpiryTime();

}
