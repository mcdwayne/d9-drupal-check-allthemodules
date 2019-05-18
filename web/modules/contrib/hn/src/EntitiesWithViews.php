<?php

namespace Drupal\hn;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * This class holds all referenced entities with their views.
 */
class EntitiesWithViews {

  /**
   * A list of entities with their view modes.
   *
   * @var EntityWithViews[]
   */
  private $entities = [];

  /**
   * Adds an entity with view mode to the entity list.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to add.
   * @param string $view_mode
   *   The view mode this entity is loaded with.
   *
   * @return \Drupal\hn\EntityWithViews
   *   Returns a holder of the entity with the view provided.
   */
  public function addEntity(EntityInterface $entity, $view_mode = 'default') {
    if (!$entity instanceof FieldableEntityInterface) {
      return NULL;
    }
    if (!isset($this->entities[$entity->uuid()])) {
      $this->entities[$entity->uuid()] = new EntityWithViews($entity);
    }
    $entity_with_views = $this->entities[$entity->uuid()];
    $entity_with_views->addViewMode($view_mode);

    return $entity_with_views;
  }

  /**
   * Returns all currently stored entities.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   All currently stored entities.
   */
  public function getEntities() {
    $entities = [];

    foreach ($this->entities as $entity_with_views) {
      $entities[] = $entity_with_views->getEntity();
    };

    return $entities;
  }

}
/**
 * Holds a single entity and one or multiple views.
 */
class EntityWithViews {

  /**
   * The entity this class holds.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  private $entity;

  /**
   * Holds all view modes as a key, and their displays as value.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface[]
   */
  private $viewModes = [];

  /**
   * EntityWithViews constructor.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that this class holds.
   */
  public function __construct(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * Returns the entity.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Adds a view mode to this entity.
   *
   * @param string $view_mode
   *   The view mode to add.
   */
  public function addViewMode($view_mode) {
    if (!isset($this->viewModes[$view_mode])) {
      $entity_type = $this->entity->getEntityTypeId();
      $bundle = $this->entity->bundle();

      $display = \Drupal::entityTypeManager()
        ->getStorage('entity_view_display')
        ->load($entity_type . '.' . $bundle . '.' . $view_mode);

      if (!$display) {
        $values = [
          'targetEntityType' => $entity_type,
          'bundle' => $bundle,
          'mode' => $view_mode,
          'status' => TRUE,
        ];
        $display = \Drupal::entityTypeManager()
          ->getStorage('entity_view_display')
          ->create($values);
      }

      $this->viewModes[$view_mode] = $display;
    }
  }

  /**
   * Returns all view modes added to this entity.
   *
   * @return string[]
   *   The view modes.
   */
  public function getViewModes() {
    return array_keys($this->viewModes);
  }

  /**
   * Returns all hidden fields' names.
   *
   * @return string[]
   *   All hidden fields.
   */
  public function getHiddenFields() {

    $hidden = [];
    foreach ($this->viewModes as $display) {
      $hidden[] = array_keys($display->toArray()['hidden']);
    }

    if (count($hidden) === 1) {
      return $hidden[0];
    }

    $hidden_fields = array_intersect(...$hidden);

    return $hidden_fields;
  }

  /**
   * Returns the display for a view mode.
   *
   * @param string $view_mode
   *   A view mode for this entity.
   *
   * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   *   The entity display.
   */
  public function getDisplay($view_mode) {
    return $this->viewModes[$view_mode];
  }

}
