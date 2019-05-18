<?php

namespace Drupal\business_rules;

/**
 * Interface ItemableInterface.
 *
 * Business Rules items which contains items.
 *
 * @package Drupal\business_rules
 */
interface ItemableInterface {

  /**
   * The Business rule's items.
   *
   * @return array
   *   Array of BusinessRulesItemObject.
   */
  public function getItems();

  /**
   * Return a list of Conditions|Actions compatible with the Rule.
   *
   * @param array $items
   *   Array of Conditions|Actions.
   *
   * @return array
   *   The available items considering the rule context.
   */
  public function filterContextAvailableItems(array $items);

}
