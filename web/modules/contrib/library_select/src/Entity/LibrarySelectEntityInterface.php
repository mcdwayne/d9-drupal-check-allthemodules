<?php

namespace Drupal\library_select\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Library Select entities.
 */
interface LibrarySelectEntityInterface extends ConfigEntityInterface {

  /**
   * Get css files.
   *
   * @return string
   *   The list of the css files.
   */
  public function getCssFiles();

  /**
   * Get js files.
   *
   * @return string
   *   The list of the js files.
   */
  public function getJsFiles();

}
