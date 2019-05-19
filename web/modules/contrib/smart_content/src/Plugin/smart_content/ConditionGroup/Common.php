<?php

namespace Drupal\smart_content\Plugin\smart_content\ConditionGroup;

use Drupal\smart_content\Annotation\SmartConditionGroup;
use Drupal\smart_content\ConditionGroup\ConditionGroupBase;

/**
 * Provides a default Smart Condition.
 *
 * @SmartConditionGroup(
 *   id = "common",
 *   label = @Translation("Common"),
 *   weight = -10
 * )
 */
class Common extends ConditionGroupBase {

}
