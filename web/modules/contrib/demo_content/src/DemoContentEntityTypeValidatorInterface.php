<?php

/**
 * @file
 * Contains \Drupal\demo_content\DemoContentEntityTypeValidatorInterface.
 */

namespace Drupal\demo_content;

/**
 * Interface DemoContentEntityTypeValidatorInterface.
 * @package Drupal\demo_content
 */
interface DemoContentEntityTypeValidatorInterface {
  
  /**
   * Returns TRUE if entity_type implements \Drupal\Core\Config\Entity\ConfigEntityInterface.
   * @return BOOL
   */
  public function isContentEntityType($entity_type);
}
