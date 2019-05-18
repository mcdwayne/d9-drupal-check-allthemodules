<?php

namespace Drupal\entity_http_exception\Event;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class to contain an EntityHttpExceptionEvent .
 */
class EntityHttpExceptionEvent extends Event {

  /**
   * The Entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  private $entity;

  /**
   * Construct a new entity event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity which caused the event.
   */
  public function __construct(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * Method to get the entity from the event.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Returns the response object.
   *
   * @return string
   *   Http code.
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * Sets a response.
   */
  public function setResponse(String $response) {
    $this->response = $response;
  }

}
