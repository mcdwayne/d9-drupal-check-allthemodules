<?php

namespace Drupal\commerce_condition_kit\Entity;

/**
 * Provides an interface for defining coupon entities.
 */
interface CouponInterface {

  /**
   * Gets the coupon usage limit per user.
   *
   * Represents the maximum number of times the coupon can be used per user.
   * 0 for unlimited.
   *
   * @return int
   *   The coupon usage limit per user.
   */
  public function getUserUsageLimit();

  /**
   * Sets the coupon usage limit per user.
   *
   * @param int $usage_limit
   *   The coupon usage limit per user.
   *
   * @return $this
   */
  public function setUserUsageLimit($usage_limit);

}
