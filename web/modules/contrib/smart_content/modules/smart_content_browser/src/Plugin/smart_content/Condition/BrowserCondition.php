<?php

namespace Drupal\smart_content_browser\Plugin\smart_content\Condition;

use Drupal\smart_content\Condition\ConditionTypeConfigurableBase;

/**
 * Provides a default Smart Condition.
 *
 * @SmartCondition(
 *   id = "browser",
 *   label = @Translation("Browser"),
 *   group = "browser",
 *   weight = 0,
 *   deriver = "Drupal\smart_content_browser\Plugin\Derivative\BrowserDerivative"
 * )
 */
class BrowserCondition extends ConditionTypeConfigurableBase {

  /**
   * @inheritdoc
   */
  public function getLibraries() {
    $libraries = array_unique(array_merge(parent::getLibraries(), ['smart_content_browser/condition.browser']));
    return $libraries;
  }

}