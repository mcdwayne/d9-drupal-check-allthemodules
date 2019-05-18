<?php

namespace Drupal\hn\Event;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * This event is invoked whenever something happens with an handled entity.
 *
 * It can be used to alter handled entity.
 */
class HnHandledEntityEvent extends Event {

  /**
   * This event is emitted as soon as the entity is handled (normalized) by
   * an entity handler. You can alter the handled entity here before adding it
   * to the response.
   */
  const POST_HANDLE = 'hn.handledentity.posthandle';

  /**
   * Creates a new HN Entity Event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that was handled.
   * @param array $handledEntity
   *   The handled entity.
   * @param string $viewMode
   *   The view mode the event is about.
   */
  public function __construct(EntityInterface $entity, array $handledEntity, $viewMode = 'default') {
    $this->entity = $entity;
    $this->handledEntity = $handledEntity;
    $this->viewMode = $viewMode;
  }

  private $entity;

  private $handledEntity;

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
   * Handled entity getter.
   *
   * @return array
   *   The handled entity.
   */
  public function getHandledEntity() {
    return $this->handledEntity;
  }

  /**
   * Entity setter.
   *
   * @param array $handledEntity
   *   The entity to set.
   */
  public function setHandledEntity(array $handledEntity) {
    $this->handledEntity = $handledEntity;
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

}
