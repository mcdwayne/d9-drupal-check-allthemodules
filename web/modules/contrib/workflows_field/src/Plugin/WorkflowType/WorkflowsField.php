<?php

namespace Drupal\workflows_field\Plugin\WorkflowType;

use Drupal\workflows\Annotation\WorkflowType;
use Drupal\workflows\Plugin\WorkflowTypeBase;
use Drupal\workflows\WorkflowInterface;
use Drupal\workflows\WorkflowTypeInterface;

/**
 * @WorkflowType(
 *   id = "workflows_field",
 *   label = @Translation("Workflows Field"),
 *   required_states = {},
 *   forms = {
 *     "configure" = "\Drupal\workflows_field\Form\WorkflowTypeConfigureForm"
 *   },
 * )
 */
class WorkflowsField extends WorkflowTypeBase {

  /**
   * {@inheritdoc}
   */
  public function getInitialState() {
    return $this->getState($this->configuration['initial_state']);
  }

}
