<?php

namespace Drupal\coupon_for_role;

/**
 * A class that holds coupon types.
 */
class CouponConstants {

  /**
   * An absolute date. Basically meaning we do not adjust the timestamp.
   */
  const ABSOLUTE_DATE_TYPE = 1;

  /**
   * A relative date.Meaning we store a relative date in the data array.
   *
   * This means the timestamp will be adjusted when the coupon
   * is claimed.
   */
  const RELATIVE_DATE_TYPE = 2;

  /**
   * The status for a coupon after someone has claimed it.
   */
  const STATUS_INACTIVE = 0;

  /**
   * The status for a coupon when its possible to claim it.
   */
  const STATUS_ACTIVE = 1;

  /**
   * The status of a coupon when it is expired.
   */
  const STATUS_EXPIRED = 2;

}
