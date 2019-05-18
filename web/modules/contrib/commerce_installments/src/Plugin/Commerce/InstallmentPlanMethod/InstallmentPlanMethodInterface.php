<?php

namespace Drupal\commerce_installments\Plugin\Commerce\InstallmentPlanMethod;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Price;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for Installment Plan Method plugins.
 */
interface InstallmentPlanMethodInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Get the installment plan bundle.
   *
   * @return string
   *   The installment plan bundle.
   */
  public function getInstallmentPlanBundle();

  /**
   * The installment bundle.
   *
   * @return string
   *   The installment bundle.
   */
  public function getInstallmentBundle();

  /**
   * Get the number of payments in which to execute the purchase.
   *
   * @return array
   *   The payment options to spread out the installments over.
   */
  public function getNumberPayments();

  /**
   * Get the time of day on which to execute the purchase.
   *
   * @return int
   *   The time of day in which to effect purchases.
   */
  public function getTime();

  /**
   * Get the timezone which to execute the purchase.
   *
   * @return string
   *   The timezone code which to effect purchases.
   */
  public function getTimezone();

  /**
   * Get the day on which to execute the purchase.
   *
   * @return string|int
   *   A day of week (string) or day of month (integer) in which to effect
   *   purchases.
   */
  public function getDay();

  /**
   * Gets the plugin label.
   *
   * This label is user-facing.
   *
   * @return string
   *   The plugin label.
   */
  public function getLabel();

  /**
   * Build the installments.
   *
   * @param OrderInterface $order
   *   The order.
   * @param int $numberPayments
   *   The number of installments to spread out the payments.
   *
   * @return \Drupal\commerce_installments\Entity\InstallmentPlanInterface
   *   The installment plan.
   */
  public function buildInstallments(OrderInterface $order, $numberPayments);

  /**
   * Get installment amounts.
   *
   * @param int $numberPayments
   *   The number of installments to spread out the payments.
   * @param \Drupal\commerce_price\Price $totalPrice
   *   The order total price.
   *
   * @return \Drupal\commerce_price\Price[]
   *   An array of installment amounts.
   */
  public function getInstallmentAmounts($numberPayments, Price $totalPrice);

  /**
   * Get installment dates.
   *
   * @param int $numberPayments
   *   The number of installments to spread out the payments.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Drupal\Component\Datetime\DateTimePlus[]
   *   An array of installment dates.
   */
  public function getInstallmentDates($numberPayments, OrderInterface $order);

}
