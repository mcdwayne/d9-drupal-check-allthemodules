<?php

namespace Drupal\commerce_condition_kit\Entity;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_promotion\Entity\Coupon as BaseCoupon;

/**
 * Extended Coupon entity class.
 *
 * @package Drupal\commerce_condition_kit\Entity
 */
class Coupon extends BaseCoupon implements CouponInterface {

  /**
   * {@inheritdoc}
   */
  public function getUserUsageLimit() {
    return $this->get('user_usage_limit')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setUserUsageLimit($usage_limit) {
    $this->set('user_usage_limit', $usage_limit);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function available(OrderInterface $order) {
    if (!parent::available($order)) {
      return FALSE;
    }

    if ($user_usage_limit = $this->getUserUsageLimit() && $order->getEmail()) {
      /** @var \Drupal\commerce_promotion\PromotionUsageInterface $usage */
      $usage = \Drupal::service('commerce_promotion.usage');
      if ($user_usage_limit <= $usage->loadByCoupon($this, $order->getEmail())) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
