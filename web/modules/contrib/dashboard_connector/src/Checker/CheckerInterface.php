<?php

namespace Drupal\dashboard_connector\Checker;

/**
 * Provides an interface for status checkers.
 */
interface CheckerInterface {

  /**
   * Gets the checks.
   *
   * @return array
   *   An array of checks.
   */
  public function getChecks();

}
