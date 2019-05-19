<?php

namespace Drupal\workflow_task\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\workflows\WorkflowInterface;

/**
 * Provides an interface for defining Workflow task type entities.
 */
interface WorkflowTaskTypeInterface extends ConfigEntityInterface {

  /**
   * Get the ids of the workflows which are allowed to be used with this task
   * type.
   *
   * @return string[]
   */
  public function getWorkflowIds();

  /**
   * Set workflow ids of the workflows which are allowed to be used with this
   * task type.
   *
   * @param string[] $workflowIds
   *
   * @return \Drupal\workflow_task\Entity\WorkflowTaskTypeInterface
   */
  public function setWorkflowIds($workflowIds);

  /**
   * Get the workflows which are allowed to be used with this task type.
   *
   * @return \Drupal\workflows\WorkflowInterface[]
   */
  public function getWorkflows();

  /**
   * Set the workflows which are allowed to be used with this task type.
   *
   * @param \Drupal\workflows\WorkflowInterface[] $workflows
   *
   * @return \Drupal\workflow_task\Entity\WorkflowTaskTypeInterface
   */
  public function setWorkflows($workflows);

  /**
   * Get the default workflow ID for new task entities.
   *
   * @return string
   */
  public function getDefaultWorkflowId();

  /**
   * Set the default workflow ID for new task entities.
   *
   * @param string $workflowId
   *
   * @return \Drupal\workflow_task\Entity\WorkflowTaskTypeInterface
   */
  public function setDefaultWorkflowId($workflowId);

  /**
   * Get the default workflow for new task entities.
   *
   * @return \Drupal\workflows\WorkflowInterface
   */
  public function getDefaultWorkflow();

  /**
   * Set the default workflow for new task entities.
   *
   * @param \Drupal\workflows\WorkflowInterface $workflow
   *
   * @return \Drupal\workflow_task\Entity\WorkflowTaskTypeInterface
   */
  public function setDefaultWorkflow(WorkflowInterface $workflow);

}
