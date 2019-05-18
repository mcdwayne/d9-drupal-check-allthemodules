<?php

namespace Drupal\bibcite;

/**
 * Define an interface for Help service.
 */
/**
 * Interface HelpInterface.
 *
 * @package Drupal\bibcite
 */
interface HelpServiceInterface {

  /**
   * Get help text from file.
   */
  public function getHelpMarkup($links, $route, $module);

}
