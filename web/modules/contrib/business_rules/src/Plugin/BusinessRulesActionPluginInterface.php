<?php

namespace Drupal\business_rules\Plugin;

use Drupal\business_rules\ActionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;

/**
 * Defines an interface for Business rules Action plugins.
 */
interface BusinessRulesActionPluginInterface extends BusinessRulesItemPluginInterface {

  /**
   * Execute the action.
   *
   * @param \Drupal\business_rules\ActionInterface $action
   *   The configured action.
   * @param \Drupal\business_rules\Events\BusinessRulesEvent $event
   *   The event that has triggered the action.
   *
   * @return array
   *   The render array to be showed on debug block.
   */
  public function execute(ActionInterface $action, BusinessRulesEvent $event);

}
