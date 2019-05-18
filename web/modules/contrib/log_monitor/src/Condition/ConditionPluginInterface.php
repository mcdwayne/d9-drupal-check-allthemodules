<?php

namespace Drupal\log_monitor\Condition;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines an interface for Condition plugin plugins.
 */
interface ConditionPluginInterface extends PluginInspectionInterface {

  public function queryCondition($query);
}
