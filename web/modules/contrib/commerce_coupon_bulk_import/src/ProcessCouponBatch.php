<?php

namespace Drupal\commerce_coupon_bulk_import;

use Drupal\commerce_promotion\Entity\Coupon;
use Drupal\commerce_promotion\Entity\Promotion;

/**
 * Batch class to process coupons.
 */
class ProcessCouponBatch {

  /**
   * Callback defined on the batch for process every order individually.
   *
   * @param string $couponCode
   *   The desired code of the coupon to be added.
   * @param int $promotionId
   *   The promotion to add this coupon to.
   * @param int $numberOfUses
   *   The allowed number of uses for this coupon. Leave NULL for unlimited.
   * @param array $context
   *   The batch context.
   */
  public static function processCoupon(
    $couponCode,
    $promotionId,
    $numberOfUses,
    array &$context
  ) {

    self::addCouponToPromotion($couponCode, $promotionId, $numberOfUses);
    $context['message'] = t('Creating coupon: @coupon_code - Promotion ID: @promotion_id', ['@coupon_code' => $couponCode, '@promotion_id' => $promotionId]);

    $context['results'][] = $results;
  }

  /**
   * Add a coupon code to a promotion.
   *
   * @param string $couponCode
   *   The desired code of the coupon to be added.
   * @param int $promotionId
   *   The promotion to add this coupon to.
   * @param int $numberOfUses
   *   The allowed number of uses for this coupon. Leave NULL for unlimited.
   */
  public function addCouponToPromotion($couponCode, $promotionId, $numberOfUses = NULL) {
    // Create the coupon from the CSV file.
    $coupon = Coupon::create([
      'code' => $couponCode,
      'status' => TRUE,
      'promotion_id' => $promotionId,
      'usage_limit' => $numberOfUses,
    ]);
    $coupon->save();

    // Add the coupon to a promotion.
    $promotion = Promotion::load($promotionId);
    $promotion->addCoupon($coupon);
    $promotion->save();
  }

  /**
   * Callback that check the end of the batch process.
   *
   * @param bool $success
   *   Boolean to flag if batch run was successful.
   * @param array $results
   *   Results of processed batch operations.
   * @param array $operations
   *   Operations ran in the batch job.
   */
  public function processCouponsFinishedCallback(
    $success,
  array $results,
  array $operations
  ) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One coupon processed.', '@count coupons processed.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }

    drupal_set_message($message);

  }

}
