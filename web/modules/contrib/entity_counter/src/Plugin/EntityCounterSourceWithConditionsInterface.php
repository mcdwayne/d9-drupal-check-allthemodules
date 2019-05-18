<?php

namespace Drupal\entity_counter\Plugin;

use Drupal\Core\Plugin\ObjectWithPluginCollectionInterface;

/**
 * Defines the interface for entity counter sources.
 *
 * @see \Drupal\entity_counter\Annotation\EntityCounterSource
 * @see \Drupal\entity_counter\Plugin\EntityCounterSourceBase
 * @see \Drupal\entity_counter\Plugin\EntityCounterSourceManagerInterface
 * @see plugin_api
 */
interface EntityCounterSourceWithConditionsInterface extends EntityCounterSourceInterface, ObjectWithPluginCollectionInterface {

  /**
   * Ensures that the conditions pass.
   *
   * @return bool
   *   TRUE if the conditions pass, FALSE otherwise.
   */
  public function evaluateConditions();

  /**
   * Returns the conditions used for this entity counter source.
   *
   * @return \Drupal\Core\Condition\ConditionInterface[]|\Drupal\Core\Condition\ConditionPluginCollection
   *   An array of configured condition plugins.
   */
  public function getConditions();

  /**
   * Gets the values for the needed contexts.
   *
   * @return array
   *   An array of key value pairs for the contexts.
   */
  public function getConditionsContext();

  /**
   * Sets the values for the needed contexts.
   *
   * @param array $contexts
   *   An array of key value pairs for the contexts.
   *
   * @return $this
   */
  public function setConditionsContext(array $contexts);

  /**
   * Adds a new condition to the entity counter source.
   *
   * @param array $configuration
   *   An array of configuration for the new condition.
   *
   * @return string
   *   The condition ID.
   */
  public function addCondition(array $configuration);

  /**
   * Retrieves a specific condition.
   *
   * @param string $condition_id
   *   The condition ID.
   *
   * @return \Drupal\Core\Condition\ConditionInterface
   *   The condition object.
   */
  public function getCondition($condition_id);

  /**
   * Removes a specific condition.
   *
   * @param string $condition_id
   *   The condition ID.
   *
   * @return $this
   */
  public function removeCondition($condition_id);

  /**
   * Returns the logic used, either 'and' or 'or'.
   *
   * @return string
   *   The string 'and', or the string 'or'.
   */
  public function getConditionsLogic();

  /**
   * Sets the logic used, either 'and' or 'or'.
   *
   * @param string $logic
   *   The logic string.
   *
   * @return $this
   */
  public function setConditionsLogic($logic);

}
