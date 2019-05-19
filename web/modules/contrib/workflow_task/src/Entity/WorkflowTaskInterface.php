<?php

namespace Drupal\workflow_task\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\workflows\StateInterface;
use Drupal\workflows\WorkflowInterface;

/**
 * Provides an interface for defining Workflow task entities.
 *
 * @ingroup workflow_task
 */
interface WorkflowTaskInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Workflow task name.
   *
   * @return string
   *   Name of the Workflow task.
   */
  public function getName();

  /**
   * Sets the Workflow task name.
   *
   * @param string $name
   *   The Workflow task name.
   *
   * @return \Drupal\workflow_task\Entity\WorkflowTaskInterface
   *   The called Workflow task entity.
   */
  public function setName($name);

  /**
   * Gets the Workflow task creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Workflow task.
   */
  public function getCreatedTime();

  /**
   * Sets the Workflow task creation timestamp.
   *
   * @param int $timestamp
   *   The Workflow task creation timestamp.
   *
   * @return \Drupal\workflow_task\Entity\WorkflowTaskInterface
   *   The called Workflow task entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Workflow task revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Workflow task revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\workflow_task\Entity\WorkflowTaskInterface
   *   The called Workflow task entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Workflow task revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Workflow task revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\workflow_task\Entity\WorkflowTaskInterface
   *   The called Workflow task entity.
   */
  public function setRevisionUserId($uid);

  /**
   * Get the string ID representation of the workflow linked to the task.
   *
   * @return string
   */
  public function getWorkflowId();

  /**
   * Set the string ID representation of the workflow linked to the task.
   *
   * @param string $workflowId
   *
   * @return \Drupal\workflow_task\Entity\WorkflowTaskInterface
   *   The called Workflow task entity.
   */
  public function setWorkflowId($workflowId);

  /**
   * Get the workflow linked to the task.
   *
   * @return \Drupal\workflows\WorkflowInterface
   */
  public function getWorkflow();

  /**
   * Set the workflow linked to the task.
   *
   * @param \Drupal\workflows\WorkflowInterface $workflow
   *
   * @return \Drupal\workflow_task\Entity\WorkflowTaskInterface
   *   The called Workflow task entity.
   */
  public function setWorkflow(WorkflowInterface $workflow);

  /**
   * Get the string ID representation of the state the task is in.
   *
   * @return string
   */
  public function getStateId();

  /**
   * Set the string ID representation of the state the task is in.
   *
   * @param string $stateId
   *
   * @return mixed
   */
  public function setStateId($stateId);

  /**
   * Get the state the task is currently in.
   *
   * @return \Drupal\workflows\StateInterface
   */
  public function getState();

  /**
   * Set the state the task is currently in.
   *
   * @param \Drupal\workflows\StateInterface $state
   *
   * @return \Drupal\workflow_task\Entity\WorkflowTaskInterface
   *   The called Workflow task entity.
   */
  public function setState(StateInterface $state);

}
