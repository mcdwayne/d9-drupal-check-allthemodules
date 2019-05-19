<?php

namespace Drupal\style_management;

/**
 * Interface CompilerServiceInterface.
 *
 * @package Drupal\style_management
 */
interface CompilerServiceInterface {

  /**
   * Compile all files on system.
   *
   * @return array
   *   The array with merged data scss and less.
   */
  public function compileAll();

  /**
   * Get all variables present on files.
   *
   * @param string $path
   *   The full string path of file.
   *
   * @return array
   *   The array with key value of variables.
   */
  public function getVariablesLessFromPath($path);

}
