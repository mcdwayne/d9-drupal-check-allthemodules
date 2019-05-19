<?php

namespace Drupal\simple_redirect\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Simple Redirect entities.
 */
interface SimpleRedirectInterface extends ConfigEntityInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Get the from url.
   *
   * @return string
   */
  public function getFrom();

  /**
   * Get the to url.
   *
   * @return string
   */
  public function getTo();

}
