<?php

namespace Drupal\commerce_xero;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface;

/**
 * Describes how a Commerce Xero plugin should be implemented.
 */
interface CommerceXeroDataTypePluginInterface extends ConfigurablePluginInterface, PluginInspectionInterface {

  /**
   * Make a bank transaction.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment to use.
   * @param \Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface $strategy
   *   The strategy to use.
   *
   * @return \Drupal\xero\TypedData\XeroTypeInterface
   *   The typed data data type.
   */
  public function make(PaymentInterface $payment, CommerceXeroStrategyInterface $strategy);

}
