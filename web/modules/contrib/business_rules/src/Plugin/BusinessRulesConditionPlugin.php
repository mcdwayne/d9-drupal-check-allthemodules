<?php

namespace Drupal\business_rules\Plugin;

use Drupal\business_rules\ConditionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;

/**
 * Base class for Business rules Condition plugins.
 */
abstract class BusinessRulesConditionPlugin extends BusinessRulesItemPluginBase implements BusinessRulesConditionPluginInterface {

  /**
   * {@inheritdoc}
   */
  abstract public function process(ConditionInterface $condition, BusinessRulesEvent $event);

}
