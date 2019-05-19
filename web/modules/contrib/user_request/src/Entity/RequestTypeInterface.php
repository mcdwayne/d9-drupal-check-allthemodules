<?php

namespace Drupal\user_request\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for Request Type entity.
 */
interface RequestTypeInterface extends ConfigEntityInterface {

  /**
   * Sets the workflow used by this request type.
   *
   * @param string $workflow_id
   *   The workflow's ID.
   */
  public function setWorkflow($workflow_id);

  /**
   * Gets the ID of the workflow used by this request type.
   *
   * @return string
   *   The workflow ID.
   */
  public function getWorkflow();

  /**
   * Sets the response type (bundle) accepted by this request type.
   *
   * @param string $response_type
   *   The response type.
   */
  public function setResponseType($response_type);

  /**
   * Gets the response type accepted by this request type.
   *
   * @return string
   *   The response type.
   */
  public function getResponseType();

  /**
   * Sets the transitions that are performed upon response submission.
   *
   * @param string[] $transitions
   *   A list of transition keys as they are provided in the workflow.
   */
  public function setResponseTransitions($transitions);

  /**
   * Gets the transitions to be performed upon response submission.
   *
   * @return string[]
   *   A list of transition keys as they are provided in the workflow.
   */
  public function getResponseTransitions();

  /**
   * Checks if a transitions should be performed upon response submission.
   *
   * @param string $transition
   *   The transition key.
   *
   * @return bool
   *   Returns TRUE if the specified transition should be performed upon 
   *   response submission. Otherwise, returns FALSE.
   */
  public function isResponseTransition($transition);

  /**
   * Gets the transition performed when a response is deleted.
   *
   * @return string
   *   The transition key as provided in the workflow.
   */
  public function getDeletedResponseTransition();

  /**
   * Gets the configured message IDs.
   *
   * @return array
   *   An array containing the following keys:
   *   - request_sent
   *   - request_received
   *   - transitions (an array whose keys are transition IDs)
   */
  public function getMessages();

}
