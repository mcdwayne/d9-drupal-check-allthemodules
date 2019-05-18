<?php

/**
 * @file
 * Contains \Drupal\naming\NamingCategoryInterface.
 */

namespace Drupal\naming;

/**
 * Provides an interface defining a NamingCategory.
 */
interface NamingCategoryInterface {

  /**
   * Returns the NamingCategory content.
   *
   * @return array
   *   The NamingCategory content.
   */
  public function getContent();

  /**
   * Returns the NamingCategory weight.
   *
   * @return array
   *   The NamingCategory weight.
   */
  public function getWeight();

}
