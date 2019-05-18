<?php

namespace Drupal\business_rules;

use Drupal\business_rules\Events\BusinessRulesEvent;

/**
 * Provides an interface for defining Condition entities.
 */
interface ConditionInterface extends ItemInterface {

  /**
   * If it's a reverse condition (NOT).
   *
   * @return bool
   *   If the condition is reverse.
   */
  public function isReverse();

  /**
   * Get all condition's success items.
   *
   * @return array
   *   Array of items to be executed if the condition succeed.
   */
  public function getSuccessItems();

  /**
   * Get all condition's fail items.
   *
   * @return array
   *   Array of items to be executed if the condition fails.
   */
  public function getFailItems();

  /**
   * Remove one success item from the condition.
   *
   * @param \Drupal\business_rules\BusinessRulesItemObject $item
   *   The item to be removed.
   */
  public function removeSuccessItem(BusinessRulesItemObject $item);

  /**
   * Remove one fail item from the condition.
   *
   * @param \Drupal\business_rules\BusinessRulesItemObject $item
   *   The item to be removed.
   */
  public function removeFailItem(BusinessRulesItemObject $item);

  /**
   * Return a list of Conditions|Actions compatible with the Condition.
   *
   * @param array $items
   *   Array of items.
   *
   * @return array
   *   The available items considering the condition context.
   */
  public function filterContextAvailableItems(array $items);

  /**
   * Get the current max item weight.
   *
   * @param bool $success
   *   - TRUE for items for condition succeed.
   *   - FALSE for items for condition fails.
   *
   * @return int
   *   The current max item weight.
   */
  public function getMaxItemWeight($success = TRUE);

  /**
   * Add one item for Condition success.
   *
   * @param \Drupal\business_rules\BusinessRulesItemObject $item
   *   The item to be added.
   */
  public function addSuccessItem(BusinessRulesItemObject $item);

  /**
   * Add one item for Condition fail.
   *
   * @param \Drupal\business_rules\BusinessRulesItemObject $item
   *   The item to be added.
   */
  public function addFailItem(BusinessRulesItemObject $item);

  /**
   * Process the condition.
   *
   * @param \Drupal\business_rules\Events\BusinessRulesEvent $event
   *   The event that has triggered the condition.
   *
   * @return bool
   *   Boolean value that indicates if the condition is true.
   *
   * @throws \ReflectionException
   */
  public function process(BusinessRulesEvent $event);

}
