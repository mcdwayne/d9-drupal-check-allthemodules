<?php

namespace Drupal\access_filter\Plugin;

use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an interface defining a filter condition.
 */
interface ConditionInterface {

  /**
   * Gets summary text for the condition.
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
   * Checks the current access is matched to the condition.
   *
   * @param Request $request
   *   A request instance.
   *
   * @return bool
   *   Boolean TRUE if condition is matched or FALSE otherwise.
   */
  public function isMatched(Request $request);

  /**
   * Determines whether condition result will be negated.
   *
   * @return bool
   *   Whether the condition result will be negated.
   */
  public function isNegated();

}
