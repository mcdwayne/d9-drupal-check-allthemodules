<?php

namespace Drupal\braintree_cashier\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Braintree Cashier discount entities.
 *
 * @ingroup braintree_cashier
 */
interface BraintreeCashierDiscountInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the discount name.
   *
   * @return string
   *   Name of the discount.
   */
  public function getName();

  /**
   * Gets the environment of the discount.
   *
   * @return string
   *   The environment.
   */
  public function getEnvironment();

  /**
   * Sets the discount name.
   *
   * @param string $name
   *   The discount name.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierDiscountInterface
   *   The called discount entity.
   */
  public function setName($name);

  /**
   * Gets the discount creation timestamp.
   *
   * @return int
   *   Creation timestamp of the discount.
   */
  public function getCreatedTime();

  /**
   * Sets the discount creation timestamp.
   *
   * @param int $timestamp
   *   The discount creation timestamp.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierDiscountInterface
   *   The called discount entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the discount published status indicator.
   *
   * Unpublished discount are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the discount is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a discount.
   *
   * @param bool $published
   *   TRUE to set this discount to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\braintree_cashier\Entity\BraintreeCashierDiscountInterface
   *   The called discount entity.
   */
  public function setPublished($published);

  /**
   * Gets the Braintree discount ID.
   *
   * @return string
   *   The discount ID in the Braintree console.
   */
  public function getBraintreeDiscountId();

}
