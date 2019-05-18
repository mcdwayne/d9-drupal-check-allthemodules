<?php

namespace Drupal\commerce_vl;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\user\UserInterface;

/**
 * Interface ViralLoopsIntegratorInterface.
 */
interface ViralLoopsIntegratorInterface {

  /**
   * Viral Loops API events URL.
   */
  const VL_API_EVENTS_URL = 'https://app.viral-loops.com/api/v2/events';

  /**
   * Viral Loops API redeem URL.
   */
  const VL_API_REDEEM_URL = 'https://app.viral-loops.com/api/v2/rewarded';

  /**
   * Queue name for Viral Loops coupon redemption.
   */
  const COUPON_REDEEM_CRON_QUEUE = 'viral_loops_request_coupon_redeem';

  /**
   * Queue name for Viral Loops completed order data processing.
   */
  const COMPLETED_ORDER_CRON_QUEUE = 'viral_loops_process_completed_order_data';

  /**
   * Collect needed data for the Viral Loops widget script.
   *
   * @return array
   *   An array with data for the Viral Loops widget snippet.
   *   See viral-loops.js
   */
  public function getWidgetData();

  /**
   * Prepares user data for the Viral Loops script.
   *
   * @return array
   *   An array with user data which is needed on viral-loops.js.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getClientIdentifyUserData();

  /**
   * Check if there is a marker to logout a user from Viral Loops.
   *
   * @return bool
   *   TRUE - user needs to be logged out.
   */
  public function needLogout();

  /**
   * Identify user on Viral Loops server side.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account has been just logged in.
   *
   * @return false|string
   *   Return a referral code or FALSE if a request to VL has been failed.
   */
  public function sendServerIdentifyUserRequest(UserInterface $account);

  /**
   * Processing Commerce Order after it has been completed.
   *
   * Try to create a new viral loop coupon. If it does get created we can
   * proceed to create it to drupal too.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   Completed Commerce Order entity.
   */
  public function handleOrderCompletion(OrderInterface $order);

  /**
   * API POST request to mark a coupon as redeemed.
   *
   * @param string $reward_id
   *   The viral loops reward id.
   *
   * @return object|false
   *   Viral Loops API response object.
   */
  public function redeemViralLoopsCoupon($reward_id);

  /**
   * Return Viral Loops promotion.
   *
   * @param bool $return_id
   *   TRUE - returns only Promotion entity ID.
   *
   * @return null|\Drupal\commerce_promotion\Entity\Promotion
   *   Viral Loops commerce promotion.
   */
  public function getViralLoopsPromotion($return_id = FALSE);

  /**
   * Found and return Viral Loops coupon in the Order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   Commerce Order entity.
   *
   * @return \Drupal\commerce_promotion\Entity\Coupon|null
   *   Viral Loops commerce coupon.
   */
  public function getOrderViralLoopsCoupon(OrderInterface $order);

  /**
   * Get all Viral Loops coupons applied to the Order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   Commerce Order entity.
   *
   * @return array
   *   An array of Commerce Coupons.
   */
  public function getOrderViralLoopsCoupons(OrderInterface $order);

  /**
   * Send completed order data to Viral Loops.
   *
   * @param array $request_data
   *   The data to send to the Viral Loops API.
   * @param string $currency_code
   *   Currency code.
   */
  public function processCompletedOrderData(array $request_data, $currency_code);

}
