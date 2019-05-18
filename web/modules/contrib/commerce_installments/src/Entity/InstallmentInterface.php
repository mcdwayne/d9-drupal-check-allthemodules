<?php

namespace Drupal\commerce_installments\Entity;

use Drupal\commerce_price\Price;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Installment entities.
 *
 * @ingroup commerce_installments
 */
interface InstallmentInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Installment creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Installment.
   */
  public function getCreatedTime();

  /**
   * Sets the Installment creation timestamp.
   *
   * @param int $timestamp
   *   The Installment creation timestamp.
   *
   * @return \Drupal\commerce_installments\Entity\InstallmentInterface
   *   The called Installment entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the installment payment state.
   *
   * @return \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface
   *   The installment payment state.
   */
  public function getState();

  /**
   * Sets the payment state.
   *
   * @param string $state_id
   *   The new state ID.
   *
   * @return $this
   */
  public function setState($state_id);

  /**
   * Sets the date (timestamp) to process installment payment.
   *
   * @param int $timestamp
   *   The installment payment timestamp.
   *
   * @return $this
   */
  public function setPaymentDate($timestamp);

  /**
   * Gets the date (timestamp) to process installment payment.
   *
   * @return int
   *   The installment payment timestamp.
   */
  public function getPaymentDate();

  /**
   * Gets the payment amount.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The payment amount, or NULL.
   */
  public function getAmount();

  /**
   * Sets the payment amount.
   *
   * @param \Drupal\commerce_price\Price $amount
   *   The payment amount.
   *
   * @return $this
   */
  public function setAmount(Price $amount);

  /**
   * Get the related installment plan.
   *
   * @return \Drupal\commerce_installments\Entity\InstallmentPlanInterface
   */
  public function getInstallmentPlan();

}
