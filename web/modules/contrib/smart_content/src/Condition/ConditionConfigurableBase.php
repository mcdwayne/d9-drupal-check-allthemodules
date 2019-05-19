<?php

namespace Drupal\smart_content\Condition;

use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\smart_content\Condition\ConditionBase;

/**
 * Class ConditionConfigurableBase.
 *
 * @package Drupal\smart_content\Condition
 *
 * This is a base class for conditions requiring a configuration form,
 * without the need for using the type system.  This should primarily be used
 * for one-off conditions where type-like functionality is unlikely.
 */
abstract class ConditionConfigurableBase extends ConditionBase implements PluginFormInterface {

}