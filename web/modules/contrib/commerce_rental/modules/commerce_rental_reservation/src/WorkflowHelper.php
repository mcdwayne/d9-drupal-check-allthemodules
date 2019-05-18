<?php

namespace Drupal\commerce_rental_reservation;

use Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface;

class WorkflowHelper implements WorkflowHelperInterface {

  public function getStateDefinition(StateItemInterface $state) {
    $value = $state->getValue()['value'];
    $state_definitions = $state->getWorkflow()->getPluginDefinition()['states'];
    return $state_definitions[$value];
  }

  public function getStatePriority(StateItemInterface $state) {
    $state_definition = $this->getStateDefinition($state);
    return $state_definition['priority'] ? $state_definition['priority'] : NULL;
  }

  public function isStateBlocking(StateItemInterface $state) {
    $state_definition = $this->getStateDefinition($state);
    return $state_definition['blocking'] ? $state_definition['blocking'] : NULL;
  }

}