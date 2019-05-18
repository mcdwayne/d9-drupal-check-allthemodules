<?php

namespace Drupal\commerce_braintree_marketplace\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;

interface MarketplaceInterface {

  /**
   * Hold a payment in escrow.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   */
  public function holdInEscrow(PaymentInterface $payment);

  /**
   * Cancel escrow release.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   */
  public function cancelRelease(PaymentInterface $payment);

  /**
   * Release a payment from escrow.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   */
  public function releaseFromEscrow(PaymentInterface $payment);

}
