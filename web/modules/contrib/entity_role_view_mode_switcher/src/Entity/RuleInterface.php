<?php

namespace Drupal\entity_role_view_mode_switcher\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining View Mode Switcher Rule entities.
 */
interface RuleInterface extends ConfigEntityInterface {

  /**
   * Set conditions on the rule from an array, replacing any existing.
   *
   * @param array $conditionsArray
   *   Array of conditions to add to the rule.
   *
   * @return $this
   */
  public function setConditionsFromArray(array $conditionsArray);

  /**
   * Get the rule's conditions.
   *
   * @return array
   *   Array of conditions.
   */
  public function getConditions();

}
