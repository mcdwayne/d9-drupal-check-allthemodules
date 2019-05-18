<?php

namespace Drupal\entity_counter_commerce\Plugin\EntityCounterSource;

use Drupal\entity_counter_commerce\Plugin\CommerceEntityCounterSourceBase;

/**
 * Adds orders amount source to entity counters.
 *
 * @EntityCounterSource(
 *   id = "entity_counter_commerce_orders_amount",
 *   label = @Translation("Orders amount source"),
 *   description = @Translation("Sum the orders amount."),
 *   cardinality = \Drupal\entity_counter\EntityCounterSourceCardinality::UNLIMITED,
 *   value_type = \Drupal\entity_counter\EntityCounterSourceValue::INCREMENTAL
 * )
 */
class OrdersAmountSource extends CommerceEntityCounterSourceBase {

  /**
   * {@inheritdoc}
   */
  protected function getConditionOptionsForm() {
    static $options = NULL;

    if ($options === NULL) {
      foreach ($this->conditionManager->getFilteredDefinitions('entity_source', ['commerce_order']) as $plugin_id => $definition) {
        $options[$plugin_id] = (string) $definition['label'];
      }
    }

    return $options;
  }

}
