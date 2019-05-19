<?php

namespace Drupal\workflow_task\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\workflows\WorkflowInterface;

/**
 * Defines the Workflow task type entity.
 *
 * @ConfigEntityType(
 *   id = "workflow_task_type",
 *   label = @Translation("Workflow task type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\workflow_task\WorkflowTaskTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\workflow_task\Form\WorkflowTaskTypeForm",
 *       "edit" = "Drupal\workflow_task\Form\WorkflowTaskTypeForm",
 *       "delete" = "Drupal\workflow_task\Form\WorkflowTaskTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\workflow_task\WorkflowTaskTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "workflow_task_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "workflow_task",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/workflow_task_type/{workflow_task_type}",
 *     "add-form" = "/admin/structure/workflow_task_type/add",
 *     "edit-form" = "/admin/structure/workflow_task_type/{workflow_task_type}/edit",
 *     "delete-form" = "/admin/structure/workflow_task_type/{workflow_task_type}/delete",
 *     "collection" = "/admin/structure/workflow_task_type"
 *   }
 * )
 */
class WorkflowTaskType extends ConfigEntityBundleBase implements WorkflowTaskTypeInterface {

  /**
   * The Workflow task type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Workflow task type label.
   *
   * @var string
   */
  protected $label;

  /**
   * The workflows allowed to work with this task type.
   *
   * @var string[]
   */
  protected $workflows;

  /**
   * The default workflow to be used for newly created tasks of this config
   * type.
   *
   * @var string
   */
  protected $default_workflow;

  /**
   * @inheritDoc
   */
  public function getWorkflows() {
    return $this->entityTypeManager()
      ->getStorage('workflow')
      ->loadMultiple($this->getWorkflowIds());
  }

  /**
   * @inheritDoc
   */
  public function setWorkflows($workflows) {
    $workflowIds = [];
    foreach ($workflows as $workflow) {
      $workflowIds[$workflow->id()] = $workflow->id();
    }
    $this->setWorkflowIds($workflowIds);
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function getWorkflowIds() {
    return $this->workflows;
  }

  /**
   * @inheritDoc
   */
  public function setWorkflowIds($workflowIds) {
    $this->workflows = $workflowIds;
    return $this;
  }

  /**
   * @inheritDoc
   */
  public function setDefaultWorkflowId($workflowId) {
    $this->default_workflow = $workflowId;
  }

  /**
   * @inheritDoc
   */
  public function getDefaultWorkflow() {
    return $this->entityTypeManager()
      ->getStorage('workflow')
      ->load($this->getDefaultWorkflowId());
  }

  /**
   * @inheritDoc
   */
  public function setDefaultWorkflow(WorkflowInterface $workflow) {
    $this->setDefaultWorkflowId($workflow->id());
  }

  /**
   * @inheritDoc
   */
  public function getDefaultWorkflowId() {
    return $this->default_workflow;
  }


}
