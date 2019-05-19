<?php

/**
 * @file
 * Definition of EntityStorageControllerStub.
 */

namespace Drupal\wow\Mocks;

/**
 * Storage controller stub for realms.
 */
class EntityStorageControllerStub implements \EntityAPIControllerInterface {

  public $entities = array();

  public function resetCache(array $ids = NULL) {}

  public function load($ids = array(), $conditions = array()) {
    $entities = array();

    if (empty($ids) && !empty($conditions)) {
      foreach ($conditions as $key => $value) {
        // Build the array.
        foreach ($this->entities as $id => $entity) {
          if (isset($entity->{$key}) && $entity->{$key} == $value) {
            $entities[$id] = $entity;
          }
        }

      }
    }

    return $entities;
  }

  public function delete($ids) {}

  public function invoke($hook, $entity) {}

  public function save($entity) {}

  public function create(array $values = array()) {}

  public function export($entity, $prefix = '') {}

  public function import($export) {}

  public function buildContent($entity, $view_mode = 'full', $langcode = NULL) {}

  public function view($entities, $view_mode = 'full', $langcode = NULL, $page = NULL) {}

}
