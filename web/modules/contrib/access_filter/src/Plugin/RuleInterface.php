<?php

namespace Drupal\access_filter\Plugin;

use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an interface defining a filter rule.
 */
interface RuleInterface {

  /**
   * Gets summary text for the rule.
   *
   * @return string
   *   A summary string.
   */
  public function summary();

  /**
   * Validates configuration data.
   *
   * @param array $configuration
   *   The array containing configurations.
   *
   * @return array
   *   An array of error messages.
   */
  public function validateConfiguration(array $configuration);

  /**
   * Checks the current access by the rule.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function check(Request $request);

}
