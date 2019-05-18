<?php

namespace Drupal\business_rules;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Rule entities.
 */
interface BusinessRuleInterface extends ConfigEntityInterface {

  /**
   * Return all types of reactions events for business rules.
   *
   * @return array
   *   Array of event types.
   */
  public static function getEventTypes();

  /**
   * Load all Business Rule's tags.
   *
   * @return array
   *   Array of tags.
   */
  public static function loadAllTags();

  /**
   * Add one item on the Business Rule's items.
   *
   * @param \Drupal\business_rules\BusinessRulesItemObject $item
   *   The item to be added.
   */
  public function addItem(BusinessRulesItemObject $item);

  /**
   * Check if the item is on the same context as the Business Rule.
   *
   * @param \Drupal\business_rules\BusinessRulesItemObject $itemObject
   *   The business rule object.
   *
   * @return bool
   *   If the item is on the same context as the business rule.
   */
  public function checkItemContext(BusinessRulesItemObject $itemObject);

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

  /**
   * Get the current Business Rule's actions.
   *
   * @return array
   *   Array of actions.
   */
  public function getActions();

  /**
   * Get the current Business Rule's conditions.
   *
   * @return array
   *   Array of conditions.
   */
  public function getConditions();

  /**
   * The rule description.
   *
   * @return string
   *   The business rule description.
   */
  public function getDescription();

  /**
   * Get one Business Rule item.
   *
   * @param string $item_id
   *   The item id.
   *
   * @return BusinessRulesItemObject
   *   The item.
   */
  public function getItem($item_id);

  /**
   * Get the max weight for the Business Rule's items.
   *
   * @return int
   *   The max weight.
   */
  public function getItemMaxWeight();

  /**
   * The Business rule's items.
   *
   * @return array
   *   Array of BusinessRulesItemObject.
   */
  public function getItems();

  /**
   * The trigger that will start the rule.
   *
   * @return string
   *   The reacts on event id for the rule.
   */
  public function getReactsOn();

  /**
   * The label of the trigger that will start the rule.
   *
   * @var string
   *   The reacts on event label for the rule.
   */
  public function getReactsOnLabel();

  /**
   * Get the tags value.
   *
   * @return array
   *   The tags value.
   */
  public function getTags();

  /**
   * The target entity bundle id which this rule is applicable.
   *
   * @var string
   *   Context: The rule target bundle id.
   */
  public function getTargetBundle();

  /**
   * The label of the target entity bundle id which this rule is applicable.
   *
   * @var string
   *   Context: The rule target bundle label.
   */
  public function getTargetBundleLabel();

  /**
   * The entity type id which this rule is applicable.
   *
   * @var string
   *   Context: The rule target entity type id.
   */
  public function getTargetEntityType();

  /**
   * The label of the entity type id which this rule is applicable.
   *
   * @var string
   *   Context: The rule target entity type label.
   */
  public function getTargetEntityTypeLabel();

  /**
   * Returns TRUE if the BusinessRule is enabled and FALSE if not.
   *
   * @return bool
   *   If the rule is enabled.
   */
  public function isEnabled();

  /**
   * Remove one item from the Business rule's items.
   *
   * @param \Drupal\business_rules\BusinessRulesItemObject $item
   *   The item to be removed.
   */
  public function removeItem(BusinessRulesItemObject $item);

  /**
   * Set the enabled parameter.
   *
   * @param bool $status
   *   Set the enabled status: true|false.
   */
  public function setEnabled($status);

  /**
   * Set the tags value.
   *
   * @param array $tags
   *   The tags.
   */
  public function setTags(array $tags);

}
