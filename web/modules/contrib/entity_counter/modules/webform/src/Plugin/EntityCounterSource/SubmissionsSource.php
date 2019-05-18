<?php

namespace Drupal\entity_counter_webform\Plugin\EntityCounterSource;

use Drupal\entity_counter_webform\Plugin\WebformEntityCounterSourceBase;

/**
 * Adds webform submissions source to entity counters.
 *
 * @EntityCounterSource(
 *   id = "entity_counter_webform_submissions",
 *   label = @Translation("Webform submissions source"),
 *   description = @Translation("Count the number of webform submissions."),
 *   cardinality = \Drupal\entity_counter\EntityCounterSourceCardinality::UNLIMITED,
 *   value_type = \Drupal\entity_counter\EntityCounterSourceValue::INCREMENTAL
 * )
 */
class SubmissionsSource extends WebformEntityCounterSourceBase {

  /**
   * {@inheritdoc}
   */
  protected function getConditionOptionsForm() {
    static $options = NULL;

    if ($options === NULL) {
      foreach ($this->conditionManager->getFilteredDefinitions(['webform_submission']) as $plugin_id => $definition) {
        $options[$plugin_id] = (string) $definition['label'];
      }
    }

    return $options;
  }

}
