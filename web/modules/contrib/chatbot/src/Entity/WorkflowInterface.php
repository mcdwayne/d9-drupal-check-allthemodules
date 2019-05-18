<?php

namespace Drupal\chatbot\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface for defining Workflow entities.
 *
 * @ingroup chatbot
 */
interface WorkflowInterface extends  ContentEntityInterface, EntityChangedInterface {

  // Add get/set methods for your configuration properties here.
  /**
   * Gets the Workflow title.
   *
   * @return string
   *   Title of the Workflow.
   */
  public function getTitle();

  /**
   * Sets the Workflow title.
   *
   * @param string $title
   *   The Workflow title.
   *
   * @return \Drupal\chatbot\Entity\WorkflowInterface
   *   The called Workflow entity.
   */
  public function setTitle($title);

  /**
   * Gets the Workflow creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Workflow.
   */
  public function getCreatedTime();

  /**
   * Sets the Workflow creation timestamp.
   *
   * @param int $timestamp
   *   The Workflow creation timestamp.
   *
   * @return \Drupal\chatbot\Entity\WorkflowInterface
   *   The called Workflow entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Workflow published status indicator.
   *
   * Unpublished Workflow are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Workflow is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Workflow.
   *
   * @param bool $published
   *   TRUE to set this Workflow to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\chatbot\Entity\WorkflowInterface
   *   The called Workflow entity.
   */
  public function setPublished($published);

  /**
   * Returns a list of Step Entity.
   *
   * @return \Drupal\chatbot\Entity\StepInterface
   *   A list of Steps
   */
  public function getSteps();

  /**
   * Sets the entity owner's user entity.
   *
   * @param $steps
   *   A list of Steps
   *
   * @return $this
   */
  public function setSteps($steps);

}
