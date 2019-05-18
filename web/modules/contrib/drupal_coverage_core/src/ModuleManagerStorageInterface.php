<?php

namespace Drupal\drupal_coverage_core;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a common interface for module manager storage classes.
 */
interface ModuleManagerStorageInterface {

  /**
   * List the analyses that are currently being build.
   *
   * @return array|int
   *   Returns an array of modules.
   */
  public function getModules();

  /**
   * Get all analyses for a module.
   *
   * @param EntityInterface $module
   *   The module we want to fetch data for.
   *
   * @return mixed
   *   Returns the analyses for a module.
   */
  public function getAnalyses(EntityInterface $module);

  /**
   * Get the last analysis for a module.
   *
   * @param EntityInterface $module
   *   The module we want to fetch data for.
   *
   * @return EntityInterface
   *   Returns the last analysis for a module.
   */
  public function getLastAnalysis(EntityInterface $module);

  /**
   * Get the core module based on a title.
   *
   * @param string $title
   *   Module title.
   *
   * @return EntityInterface
   *   Returns the module based on a title.
   *
   * @throws \Drupal\drupal_coverage_core\Exception\ModuleDoesNotExistException
   *   When the module does not exist and needs to be created.
   */
  public function getCoreModule($title);

}
