<?php

namespace Drupal\access_conditions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Provides an interface defining a access_model entity.
 */
interface AccessModelInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Sets the label for the access model.
   *
   * @param string $label
   *   The label for the access model.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Returns the description for the access model.
   *
   * @return string
   *   The description for the access model.
   */
  public function getDescription();

  /**
   * Sets the description for the access model.
   *
   * @param string $description
   *   The description for the access model.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Returns the conditions used for determining access for this access model.
   *
   * @return \Drupal\Core\Condition\ConditionInterface[]|\Drupal\Core\Condition\ConditionPluginCollection
   *   An array of configured condition plugins.
   */
  public function getAccessConditions();

  /**
   * Adds a new access condition to the access model.
   *
   * @param array $configuration
   *   An array of configuration for the new access model.
   *
   * @return string
   *   The access model ID.
   */
  public function addAccessCondition(array $configuration);

  /**
   * Retrieves a specific access condition.
   *
   * @param string $condition_id
   *   The access model ID.
   *
   * @return \Drupal\Core\Condition\ConditionInterface
   *   The access model object.
   */
  public function getAccessCondition($condition_id);

  /**
   * Removes a specific access condition.
   *
   * @param string $condition_id
   *   The access model ID.
   *
   * @return $this
   */
  public function removeAccessCondition($condition_id);

  /**
   * Returns the logic used to compute access, either 'and' or 'or'.
   *
   * @return string
   *   The string 'and', or the string 'or'.
   */
  public function getAccessLogic();

  /**
   * Sets the logic used to compute access, either 'and' or 'or'.
   *
   * @param string $access_logic
   *   The access logic string.
   *
   * @return $this
   */
  public function setAccessLogic($access_logic);

}
