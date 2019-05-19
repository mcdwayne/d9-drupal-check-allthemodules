<?php

namespace Drupal\switches\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Switch entities.
 */
interface SwitchInterface extends ConfigEntityInterface {

  /**
   * Returns the switch description.
   *
   * @return string
   *   The switch description.
   */
  public function getDescription();

  /**
   * Sets the switch activation method.
   *
   * @return string
   *   The switch activation method.
   */
  public function getActivationMethod();

  /**
   * Sets the switch manual activation status.
   *
   * @return bool
   *   The switch manual activation status.
   */
  public function getManualActivationStatus();

  /**
   * Sets the switch activation method.
   *
   * @param string $activationMethod
   *   The activation Method.
   *
   * @return $this
   */
  public function setActivationMethod($activationMethod);

  /**
   * Sets the switch manual activation status.
   *
   * @param bool $status
   *   The manual activation status.
   *
   * @return $this
   */
  public function setManualActivationStatus($status);

  /**
   * Sets the activation condition configuration.
   *
   * @param string $instance_id
   *   The condition instance ID.
   * @param array $configuration
   *   The condition configuration.
   *
   * @return $this
   */
  public function setActivationConditionConfig($instance_id, array $configuration);

  /**
   * Get configured switch activation conditions.
   *
   * @return \Drupal\Core\Condition\ConditionInterface[]|\Drupal\Core\Condition\ConditionPluginCollection
   *   An array or collection of configured condition plugins.
   */
  public function getActivationConditions();

  /**
   * Returns an array of activation condition configurations.
   *
   * @return array
   *   An array of activation condition configuration keyed by the condition ID.
   */
  public function getActivationConditionsConfig();

  /**
   * Get a specific condition plugin instance.
   *
   * @param string $instance_id
   *   The condition plugin instance ID.
   *
   * @return \Drupal\Core\Condition\ConditionInterface
   *   A condition plugin.
   */
  public function getActivationCondition($instance_id);

  /**
   * Get the current activation status of the switch.
   *
   * @return bool
   *   The activation status of the switch.
   */
  public function getActivationStatus();

}
