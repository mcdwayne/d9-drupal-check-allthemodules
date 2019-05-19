<?php

namespace Drupal\user_restrictions\Entity;

/**
 * Defines the interface for UserRestriction entities.
 */
interface UserRestrictionInterface {

  /**
   * Get timestamp of rule expiration.
   *
   * @return int
   *   Timestamp of rule expiration.
   */
  public function getExpiry();

  /**
   * Get the pattern of the rule.
   *
   * @return string
   *   Regular expression (or exact string) to match against a value.
   */
  public function getPattern();

  /**
   * Get the type of restriction.
   *
   * @return int
   *   Either UserRestrictions::BLACKLIST or UserRestrictions::WHITELIST.
   */
  public function getAccessType();

  /**
   * Get the ID of the restrictions rule type.
   *
   * @see \Drupal\user_restrictions\Plugin\UserRestrictionTypeInterface
   *
   * @return string
   *   ID of restriction type.
   */
  public function getRuleType();

}
