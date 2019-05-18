<?php

namespace Drupal\commerce_vl\Plugin\QueueWorker;

/**
 * Sends delayed Viral Loops tracking event.
 *
 * @QueueWorker(
 *   id = "viral_loops_request_coupon_redeem",
 *   title = @Translation("Viral Loops coupon redeem request"),
 *   cron = {"time" = 60}
 * )
 */
class ViralLoopsRequestCouponRedeem extends ViralLoopsRequestBase {

  /**
   * {@inheritdoc}
   */
  protected function getMethodName() {
    return 'redeemViralLoopsCoupon';
  }

}
