<?php

namespace Drupal\commerce_installments;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;

/**
 * Defines the storage handler class for Installment Plan Method entities.
 *
 * This extends the base storage class, adding required special handling for
 * Installment Plan method entities.
 *
 * @ingroup commerce_installments
 */
interface InstallmentPlanMethodStorageInterface extends ConfigEntityStorageInterface {

  /**
   * Loads all eligible installment plan methods for the given order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   (optional) The order.
   *
   * @return \Drupal\commerce_installments\Entity\InstallmentPlanMethodInterface[]
   *   The installment plan methods.
   */
  public function loadEligible(OrderInterface $order = NULL);

}
