<?php

namespace Drupal\commerce_xero;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Basic commerce xero strategy resolution.
 */
class SimpleCommerceXeroStrategyResolver implements CommerceXeroStrategyResolverInterface {

  protected $entityTypeManager;

  /**
   * Initialize method.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity_type.manager sevice.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(PaymentInterface $payment) {
    $strategies = $this->entityTypeManager
      ->getStorage('commerce_xero_strategy')
      ->loadByProperties([
        'payment_gateway' => $payment->getPaymentGatewayId(),
      ]);
    return $strategies ? reset($strategies) : FALSE;
  }

}
