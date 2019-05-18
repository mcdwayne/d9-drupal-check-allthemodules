<?php

namespace Drupal\commerce_paytrail\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Payment method entity.
 *
 * @ConfigEntityType(
 *   id = "paytrail_payment_method",
 *   label = @Translation("Payment method"),
 *   config_prefix = "paytrail_payment_method",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 * )
 */
class PaymentMethod extends ConfigEntityBase {

  /**
   * The Payment method ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Payment method label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Payment method admin label.
   *
   * @var string
   */
  protected $adminLabel;

  /**
   * Gets the admin label.
   *
   * @return string
   *   The admin label.
   */
  public function adminLabel() : string {
    return $this->adminLabel;
  }

}
