<?php

namespace Drupal\commerce_cart_advanced;

use Drupal\commerce\ConfigurableFieldManagerInterface as FieldManagerInterface;
use Drupal\commerce_order\Entity\OrderTypeInterface;
use Drupal\entity\BundleFieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides functionality related to orders.
 */
class OrderService {

  use StringTranslationTrait;

  /**
   * The commerce configurable field manager.
   *
   * @var \Drupal\commerce\ConfigurableFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * Constructs a new OrderService object.
   *
   * @param \Drupal\commerce\ConfigurableFieldManagerInterface $field_manager
   *   The commerce configurable field manager.
   */
  public function __construct(FieldManagerInterface $field_manager) {
    $this->fieldManager = $field_manager;
  }

  /**
   * Installs the field for marking non-current carts to the given order type.
   *
   * @param \Drupal\commerce_order\Entity\OrderTypeInterface $order_type
   *   The order type to which to install the field.
   */
  public function installNonCurrentField(OrderTypeInterface $order_type) {
    $field_definition = BundleFieldDefinition::create('boolean')
      ->setTargetEntityTypeId('commerce_order')
      ->setTargetBundle($order_type->id())
      ->setName(COMMERCE_CART_ADVANCED_NON_CURRENT_FIELD_NAME)
      ->setLabel($this->t('Non-current cart'))
      ->setDescription($this->t('Indicates whether an order is a non-current cart.'));

    $this->fieldManager->createField($field_definition);
  }

}
