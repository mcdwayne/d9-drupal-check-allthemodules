<?php

namespace Drupal\drupal_coverage_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\drupal_coverage_core\Exception\ModuleDoesNotExistException;
use Drupal\node\Entity\Node;

/**
 * Defines a storage class for the module manager.
 */
class ModuleManagerStorage implements ModuleManagerStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function getModules() {
    return \Drupal::entityQuery('node')
      ->condition('type', 'module')
      ->sort('created', 'DESC')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getAnalyses(EntityInterface $module) {
    return \Drupal::entityQuery('node')
      ->condition('type', 'analysis')
      ->condition('field_module', $module->id())
      ->sort('created', 'DESC')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getLastAnalysis(EntityInterface $module) {
    $mids = \Drupal::entityQuery('node')
      ->condition('type', 'analysis')
      ->condition('field_module', $module->id())
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->execute();

    $last_mid = NULL;
    foreach ($mids as $mid) {
      $last_mid = $mid;
    }
    return $last_mid == "" ? FALSE : $last_mid;

  }

  /**
   * {@inheritdoc}
   */
  public function getCoreModule($title) {
    $mids = \Drupal::entityQuery('node')
      ->condition('type', 'module')
      ->condition('title', $title)
      ->execute();

    $module = NULL;

    foreach ($mids as $mid) {
      $module = Node::load($mid);
    }

    if (!is_object($module)) {
      throw new ModuleDoesNotExistException();
    }

    return $module;
  }

}
