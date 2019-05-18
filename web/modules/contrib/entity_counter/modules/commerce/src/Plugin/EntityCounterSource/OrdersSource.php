<?php

namespace Drupal\entity_counter_commerce\Plugin\EntityCounterSource;

use Drupal\entity_counter_commerce\Plugin\CommerceEntityCounterSourceBase;

/**
 * Adds orders source to entity counters.
 *
 * @EntityCounterSource(
 *   id = "entity_counter_commerce_orders",
 *   label = @Translation("Orders source"),
 *   description = @Translation("Count the number of orders."),
 *   cardinality = \Drupal\entity_counter\EntityCounterSourceCardinality::UNLIMITED,
 *   value_type = \Drupal\entity_counter\EntityCounterSourceValue::INCREMENTAL
 * )
 */
class OrdersSource extends CommerceEntityCounterSourceBase {

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
