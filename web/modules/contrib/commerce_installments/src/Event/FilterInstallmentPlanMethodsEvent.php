<?php

namespace Drupal\commerce_installments\Event;

use Drupal\commerce_order\Entity\OrderInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the event for filtering the available installment plan methods.
 *
 * @see \Drupal\commerce_installments\Event\InstallmentPlanMethodsEvents
 */
class FilterInstallmentPlanMethodsEvent extends Event {

  /**
   * The installment plan methods.
   *
   * @var \Drupal\commerce_installments\Entity\InstallmentPlanMethodInterface[]
   */
  protected $methods;

  /**
   * The order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Constructs a new FilterInstallmentPlanMethodsEvent object.
   *
   * @param \Drupal\commerce_installments\Entity\InstallmentPlanMethod[] $methods
   *   The installment plan methods.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   (optional) The order.
   */
  public function __construct(array $methods, OrderInterface $order = NULL) {
    $this->methods = $methods;
    $this->order = $order;
  }

  /**
   * Gets the installment plan methods.
   *
   * @return \Drupal\commerce_installments\Entity\InstallmentPlanMethodInterface[]
   *   The installment plan methods.
   */
  public function getMethods() {
    return $this->methods;
  }

  /**
   * Sets the installment plan methods.
   *
   * @param \Drupal\commerce_installments\Entity\InstallmentPlanMethodInterface[] $methods
   *   The installment plan methods.
   *
   * @return $this
   */
  public function setMethods(array $methods) {
    $this->methods = $methods;
    return $this;
  }

  /**
   * Gets the order.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface
   *   The order or NULL.
   */
  public function getOrder() {
    return $this->order;
  }

}
