<?php

namespace Drupal\user_request\Entity;

/**
 * Interface for the Response entity type.
 */
interface ResponseInterface extends ContentEntityInterface { 

  /**
   * Get the response's type.
   *
   * @return \Drupal\user_request\Entity\ResponseTypeInterface
   *   The response type entity.
   */
  public function getResponseType();

  /**
   * Gets the user who responded the request.
   * This method is an alias to EntityOwnerInterface::getOwner().
   *
   * @return \Drupal\user\UserInterface
   *   The user who responded the request.
   */
  public function respondedBy();

  /**
   * Gets the request this response belongs to.
   *
   * @return \Drupal\user_request\Entity\RequestInterface
   *   The request entity.
   */
  public function getRequest();

}
