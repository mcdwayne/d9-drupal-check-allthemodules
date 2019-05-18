<?php

namespace Drupal\core_extend\Entity;

use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Provides an interface for defining a config entity with conditions.
 */
interface ConfigEntityConditionInterface extends EntityWithPluginCollectionInterface {

  /**
   * Gets conditions for this entity.
   *
   * @return \Drupal\Core\Condition\ConditionPluginCollection
   *   A collection of configured condition plugins.
   */
  public function getConditions();

  /**
   * Gets a visibility condition plugin instance.
   *
   * @param string $instance_id
   *   The condition plugin instance ID.
   *
   * @return \Drupal\Core\Condition\ConditionInterface
   *   A condition plugin.
   */
  public function getCondition($instance_id);

  /**
   * Sets the condition configuration.
   *
   * @param string $instance_id
   *   The condition instance ID.
   * @param array $configuration
   *   The condition configuration.
   *
   * @return $this
   */
  public function setCondition($instance_id, array $configuration);

  /**
   * Gets the condition operator.
   *
   * @return string
   *   The condition operator. Possible values: AND, OR.
   */
  public function getConditionOperator();

  /**
   * Sets the condition operator.
   *
   * @param string $condition_operator
   *   The condition operator.
   *
   * @return $this
   */
  public function setConditionOperator($condition_operator);

}
