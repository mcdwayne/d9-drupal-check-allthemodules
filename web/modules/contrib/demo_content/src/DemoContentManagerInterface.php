<?php

/**
 * @file
 * Contains \Drupal\demo_content\DemoContentManagerInterface.
 */

namespace Drupal\demo_content;

/**
 * Common interface for Open Restaurant demo content.
 */
interface DemoContentManagerInterface {

  /**
   * Imports demo content from an array of content.
   *
   * @param array
   *   An array of content
   *
   * @return array[\Drupal\Core\Entity\EntityInterface
   *   The created entities.
   */
  public function import(array $content);
}
