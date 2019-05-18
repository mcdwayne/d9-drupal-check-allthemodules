<?php

namespace Drupal\eqd;

/**
 * Class Eqd
 *
 * @package Drupal\eqd
 */
class Eqd {
  private $entityType;
  private $conjunction;
  private $query;
  private $ids;
  private $entities;

  /**
   * @return mixed
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * @return mixed
   */
  public function getConjunction() {
    return $this->conjunction;
  }

  /**
   * @return mixed
   */
  public function getQuery() {
    return $this->query;
  }

  /**
   * @return mixed
   */
  public function getEntities() {
    return $this->entities;
  }

  /**
   * Get ids.
   *
   * @return mixed
   */
  public function getIds() {
    return $this->ids;
  }

  /**
   * Get the entity storage object.
   *
   * @param string $entityType
   * @param string $conjunction
   *
   * @return $this
   */
  public function getStorage($entityType = 'node', $conjunction = 'AND') {
    $this->entityType = $entityType;
    $this->conjunction = $conjunction;
    $this->createQuery();

    return $this;
  }

  /**
   * Find by fields.
   *
   * @param $fields
   *
   * @return $this
   */
  public function findBy($fields = array()) {
    $this->entities = NULL;

    foreach ($fields as $key => $field) {
      $this->query->condition($key, $field);
    }

    try {
      $this->ids = $this->query->execute();
    } catch(\Exception $e) {
      drupal_set_message('Invalid argument condition: ' . $e->getMessage(), 'error');
    }

    return $this;
  }

  /**
   * Load entities.
   */
  public function loadEntities() {
    $this->entities = array();

    if (count($this->ids) == 1) {
      $this->entities[key($this->ids)] = \Drupal::entityTypeManager()->getStorage($this->entityType)->load(key($this->ids));
    }
    elseif (count($this->ids) > 1) {
      $this->entities = \Drupal::entityTypeManager()->getStorage($this->entityType)->loadMultiple(array_keys($this->ids));
    }
  }

  /**
   * Load
   *
   * @return mixed
   */
  public function load() {
    $this->loadEntities();

    return $this->entities;
  }

  /**
   * Get values
   *
   * @param $fields
   *
   * @return array
   */
  public function getValues($fields) {
    $values = array();

    if (empty($this->entities)) {
      $this->loadEntities();
    }

    foreach ($this->entities as $entity) {
      foreach ($fields as $key => $field) {
        try {
          $values[$entity->id()][$field] = $entity->get($field)->getValue();
        } catch(\Exception $e) {
          drupal_set_message('Field ' . $field . ' is an invalid argument. Skipping.', 'error');
        }
      }
    }

    return $values;
  }

  /**
   * Get entities an array.
   *
   * @return array
   */
  public function getEntitiesArray() {
    $values = array();

    if (empty($this->entities)) {
      $this->loadEntities();
    }

    foreach ($this->entities as $entity) {
      $values[$entity->id()] = $entity->toArray();
    }

    return $values;
  }

  /**
   * Reset values;
   */
  public function reset() {
    $this->entityType = NULL;
    $this->conjunction = NULL;
    $this->query = NULL;
    $this->ids = NULL;
    $this->entities = NULL;
  }

  /**
   * Create a query.
   */
  private function createQuery() {
    $this->query = \Drupal::entityQuery($this->entityType, $this->conjunction);
  }
}