<?php

namespace Drupal\commerce_rental_reservation\Plugin\Commerce\RentalInstanceSelector;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

interface RentalInstanceSelectorPluginInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Return the name of the reusable form plugin.
   *
   * @return string
   */
  public function getName();

  /**
   * Selects a rental instance.
   *
   * @param $order_item \Drupal\commerce_order\Entity\OrderItemInterface
   *  The order item
   *
   * @return \Drupal\commerce_rental_reservation\Entity\RentalInstanceInterface|null
   *  The selected rental instance, or NULL if none available.
   */
  public function selectOrderItemInstance(OrderItemInterface $order_item);

}