<?php

namespace Drupal\user_request\Entity;

use Drupal\Core\Session\AccountInterface;

/**
 * Interface for the Request entity type.
 */
interface RequestInterface extends ContentEntityInterface { 

  /**
   * Gets the request's type.
   *
   * @return \Drupal\user_request\Entity\RequestTypeInterface
   *   The request type entity.
   */
  public function getRequestType();

  /**
   * Gets the user who made the request.
   * This method is an alias to EntityOwnerInterface::getOwner().
   *
   * @return \Drupal\user\UserInterface
   *   The user who made the request.
   */
  public function requestedBy();

  /**
   * Sets the recipients of the request. Any of them can approve or reject.
   *
   * @param \Drupal\user\UserInterface[] $recipients
   *   The recipients of the request.
   */
  public function setRecipients(array $recipients);

  /**
   * Gets the recipients.
   *
   * @return \Drupal\user\UserInterface[]
   *   The list of recipients.
   */
  public function getRecipients();

  /**
   * Gets this request's response.
   *
   * @return \Drupal\user_request\Entity\Response|null
   *   The response entity or NULL if the request does not have a response.
   */
  public function getResponse();

  /**
   * Applies a state transition.
   *
   * @param string
   *   The transition to apply.
   */
  public function applyTransition($transition);

  /**
   * Responds the request with the specified transition.
   *
   * @param string $transition
   *   The transition to perform.
   * @param \Drupal\user_request\Entity\ResponseInterface $response
   *   The response entity.
   */
  public function respond($transition, ResponseInterface $response);

  /**
   * Removes the response from this entity. The response entity is not deleted.
   */
  public function removeResponse();

  /**
   * Returns the entity removed by removeResponse().
   *
   * @return \Drupal\user_request\Entity\ResponseInterface|null
   *   The removed response if any.
   */
  public function getRemovedResponse();

  /**
   * Gets the state field item (from the state field) of the request.
   *
   * @return Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface
   *   The state field item.
   */
  public function getState();

  /**
   * Returns the current state's string code.
   *
   * @return string
   *   The current state code.
   */
  public function getStateString();

  /**
   * Checks if the request is in the specified state.
   *
   * @param string $state
   *   The state to check.
   *
   * @return bool
   *   Returns TRUE if the request is in the specified state. Otherwise, 
   *   returns FALSE.
   */
  public function inState($state);

  /**
   * Checks if this request has a response.
   *
   * @return bool
   *   Returns TRUE if this request has a response. Otherwise, returns FALSE.
   */
  public function hasResponse();

}
