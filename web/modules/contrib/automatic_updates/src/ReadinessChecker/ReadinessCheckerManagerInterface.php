<?php

namespace Drupal\automatic_updates\ReadinessChecker;

/**
 * Readiness checker manager interface.
 */
interface ReadinessCheckerManagerInterface {

  /**
   * Last checked ago warning (in seconds).
   */
  const LAST_CHECKED_WARNING = 3600 * 24;

  /**
   * Appends a checker to the checker chain.
   *
   * @param \Drupal\automatic_updates\ReadinessChecker\ReadinessCheckerInterface $checker
   *   The checker interface to be appended to the checker chain.
   * @param string $category
   *   (optional) The category of check.
   * @param int $priority
   *   (optional) The priority of the checker being added.
   *
   * @return $this
   */
  public function addChecker(ReadinessCheckerInterface $checker, $category = 'warning', $priority = 0);

  /**
   * Run checks.
   *
   * @param string $category
   *   The category of check.
   *
   * @return array
   *   An array of translatable strings.
   */
  public function run($category);

  /**
   * Get results of most recent run.
   *
   * @param string $category
   *   The category of check.
   *
   * @return array
   *   An array of translatable strings.
   */
  public function getResults($category);

  /**
   * Get timestamp of most recent run.
   *
   * @return int
   *   A unix timestamp of most recent completed run.
   */
  public function timestamp();

  /**
   * Determine if readiness checks is enabled.
   *
   * @return bool
   *   TRUE if enabled, otherwise FALSE.
   */
  public function isEnabled();

  /**
   * Get the categories of checkers.
   *
   * @return array
   *   The categories of checkers.
   */
  public function getCategories();

}
