<?php

namespace Drupal\access_conditions_commerce_promotion\Plugin\Commerce\CheckoutPane;

use Drupal\access_conditions_commerce\AccessConditionsCommerceCheckoutPaneTrait;
use Drupal\commerce_promotion\Plugin\Commerce\CheckoutPane\CouponRedemption as CouponRedemptionBase;

/**
 * Provides the coupon redemption pane with access conditions visibility.
 *
 * @CommerceCheckoutPane(
 *   id = "access_conditions_coupon_redemption",
 *   label = @Translation("Coupon redemption with access conditions"),
 *   default_step = "_disabled",
 *   wrapper_element = "container",
 * )
 */
class CouponRedemption extends CouponRedemptionBase {

  use AccessConditionsCommerceCheckoutPaneTrait;

}
