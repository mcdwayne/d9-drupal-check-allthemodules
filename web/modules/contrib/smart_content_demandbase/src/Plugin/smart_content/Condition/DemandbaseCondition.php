<?php

namespace Drupal\smart_content_demandbase\Plugin\smart_content\Condition;

use Drupal\smart_content\Condition\ConditionTypeConfigurableBase;

/**
 * Provides a Demandbase condition plugin.
 *
 * @SmartCondition(
 *   id = "demandbase",
 *   label = @Translation("Demandbase"),
 *   group = "demandbase",
 *   deriver = "Drupal\smart_content_demandbase\Plugin\Derivative\DemandbaseConditionDeriver"
 * )
 */
class DemandbaseCondition extends ConditionTypeConfigurableBase {

  /**
   * {@inheritdoc}
   */
  public function getLibraries() {
    $libraries = array_unique(array_merge(parent::getLibraries(), ['smart_content_demandbase/condition.demandbase']));
    return $libraries;
  }

}
