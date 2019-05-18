<?php

namespace Drupal\hn\Event;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * This event is invoked whenever something happens with an entity.
 *
 * It can be used to alter the entity and view mode.
 */
class HnEntityEvent extends Event {

  /**
   * This event is emitted as soon as the entity will be added to the response.
   * This means the entity is not yet handled (normalized) yet.
   */
  const ADDED = 'hn.entity.add';

  /**
   * Creates a new HN Entity Event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the event is about.
   * @param string $viewMode
   *   The view mode the event is about.
   */
  public function __construct(EntityInterface $entity, $viewMode = 'default') {
    $this->entity = $entity;
    $this->viewMode = $viewMode;
  }

  private $entity;

  private $viewMode;

  /**
   * Entity getter.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Entity setter.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to set.
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * View mode getter.
   *
   * @return string
   *   The view mode.
   */
  public function getViewMode() {
    return $this->viewMode;
  }

  /**
   * View mode setter.
   *
   * @param string $viewMode
   *   The view mode to set.
   */
  public function setViewMode($viewMode) {
    $this->viewMode = $viewMode;
  }

}
