<?php

namespace Drupal\user_restrictions;

/**
 * Defines the UserRestrictionManager interface.
 */
interface UserRestrictionsManagerInterface {

  /**
   * Check if a the given data matches any restrictions.
   *
   * @param array $data
   *   Data to check.
   *
   * @return bool
   *   TRUE if the data matches a restriction, FALSE otherwise.
   */
  public function matchesRestrictions(array $data);

  /**
   * Delete expired rules.
   *
   * @return \Drupal\user_restrictions\UserRestrictionsManagerInterface
   *   The service for chaining.
   */
  public function deleteExpiredRules();

  /**
   * Get all error messages.
   *
   * @return string[]
   *   List of error messages keyed by restriction type.
   */
  public function getErrors();

}
