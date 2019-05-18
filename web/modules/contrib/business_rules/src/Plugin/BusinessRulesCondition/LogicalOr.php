<?php

namespace Drupal\business_rules\Plugin\BusinessRulesCondition;

use Drupal\business_rules\BusinessRulesItemObject;
use Drupal\business_rules\ConditionInterface;
use Drupal\business_rules\Entity\Condition;
use Drupal\business_rules\Events\BusinessRulesEvent;

/**
 * Class LogicalOr.
 *
 * @package Drupal\business_rules\Plugin\BusinessRulesCondition
 *
 * @BusinessRulesCondition(
 *   id = "logical_or",
 *   label = @Translation("Logical OR"),
 *   group = @Translation("Logical"),
 *   description = @Translation("Logical OR condition. Returns true if at least one inner condition is valid. Only conditions with same target Entity and Bundles (if defined) can be included on the set."),
 *   reactsOnIds = {},
 *   isContextDependent = FALSE,
 * )
 */
class LogicalOr extends ConditionSet {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration = [], $plugin_id = 'logical_or', $plugin_definition = []) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // This variable lives at ConditionSet class.
    $this->entityTypeManager = $this->util->container->get('entity_type.manager');
  }

  /**
   * {@inheritdoc}
   */
  public function process(ConditionInterface $condition, BusinessRulesEvent $event) {

    $items = $condition->getSettings('items');
    $items = BusinessRulesItemObject::itemsArrayToItemsObject($items);

    /** @var \Drupal\business_rules\BusinessRulesItemObject $item */
    foreach ($items as $item) {
      $c = Condition::load($item->getId());
      if ($this->processor->isConditionValid($c, $event)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
