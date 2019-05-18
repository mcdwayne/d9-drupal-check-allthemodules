<?php

namespace Drupal\entity_counter\Plugin\EntityCounterSource;

use Drupal\entity_counter\Plugin\EntityCounterSourceBase;

/**
 * Adds manual transactions to entity counters.
 *
 * @EntityCounterSource(
 *   id = "manual",
 *   label = @Translation("Manual transactions"),
 *   description = @Translation("Allow users to add manual transactions."),
 *   cardinality = \Drupal\entity_counter\EntityCounterSourceCardinality::SINGLE,
 *   value_type = \Drupal\entity_counter\EntityCounterSourceValue::INCREMENTAL
 * )
 */
class ManualTransaction extends EntityCounterSourceBase {}
