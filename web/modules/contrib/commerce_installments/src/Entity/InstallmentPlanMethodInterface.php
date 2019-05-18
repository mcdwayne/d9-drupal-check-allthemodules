<?php

namespace Drupal\commerce_installments\Entity;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Defines the interface for installment plan method configuration entities.
 *
 * Stores configuration for installment plan method plugins.
 */
interface InstallmentPlanMethodInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Gets the installment plan method weight.
   *
   * @return string
   *   The installment plan method weight.
   */
  public function getWeight();

  /**
   * Sets the installment plan method weight.
   *
   * @param int $weight
   *   The installment plan method weight.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Gets the installment plan method plugin.
   *
   * @return \Drupal\commerce_installments\Plugin\Commerce\InstallmentPlanMethod\InstallmentPlanMethodInterface
   *   The installment plan method plugin.
   */
  public function getPlugin();

  /**
   * Gets the installment plan method plugin ID.
   *
   * @return string
   *   The installment plan method plugin ID.
   */
  public function getPluginId();

  /**
   * Sets the installment plan method plugin ID.
   *
   * @param string $plugin_id
   *   The installment plan method plugin ID.
   *
   * @return $this
   */
  public function setPluginId($plugin_id);

  /**
   * Gets the installment plan method plugin configuration.
   *
   * @return string
   *   The installment plan method plugin configuration.
   */
  public function getPluginConfiguration();

  /**
   * Sets the installment plan method plugin configuration.
   *
   * @param array $configuration
   *   The installment plan method plugin configuration.
   *
   * @return $this
   */
  public function setPluginConfiguration(array $configuration);

  /**
   * Gets the installment plan method conditions.
   *
   * @return \Drupal\commerce\Plugin\Commerce\Condition\ConditionInterface[]
   *   The installment plan method conditions.
   */
  public function getConditions();

  /**
   * Checks whether the installment plan method applies to the given order.
   *
   * Ensures that the conditions pass.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return bool
   *   TRUE if installment plan method applies, FALSE otherwise.
   */
  public function applies(OrderInterface $order);

}
