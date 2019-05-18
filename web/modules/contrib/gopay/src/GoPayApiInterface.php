<?php

namespace Drupal\gopay;

use Drupal\gopay\Payment\PaymentInterface;

/**
 * Interface GoPayApiInterface.
 *
 * @package Drupal\gopay
 */
interface GoPayApiInterface {

  /**
   * Creates \GoPay\Payments object based on configuration.
   *
   * @param array $user_config
   *   GoPay compatible configuration.
   * @param array $user_services
   *   List additional of services.
   *
   * @return \GoPay\Payments
   *   Payments object.
   */
  public function config(array $user_config, array $user_services = []);

  /**
   * Tests connection to GoPay.
   *
   * @return array
   *   Array of tested results keyed by:
   *   - token: \GoPay\Token\AccessToken object.
   */
  public function runTests();

  /**
   * Returns available payment methods.
   *
   * @return array
   *   Associative array with keys as machine name of payment method, value can
   *   be human-readable method.
   */
  public function getPaymentInstruments();

  /**
   * Creates link for GoPay gateway.
   *
   * @param \Drupal\gopay\Payment\PaymentInterface $payment
   *   Payment object.
   * @param string $text
   *   Text of link.
   *
   * @return array
   *   Render array with GoPay inline form.
   */
  public function buildLink(PaymentInterface $payment, $text = NULL);

  /**
   * Creates inline form as GoPay gateway.
   *
   * @param \Drupal\gopay\Payment\PaymentInterface $payment
   *   Payment object.
   * @param string $text
   *   Text of link.
   *
   * @return array
   *   Render array with GoPay inline form.
   */
  public function buildInlineForm(PaymentInterface $payment, $text = NULL);

  /**
   * Gets Payment status.
   *
   * @param int $id
   *   Payment Id.
   *
   * @return \GoPay\Http\Response
   *   Response object.
   */
  public function getPaymentStatus($id);

}
