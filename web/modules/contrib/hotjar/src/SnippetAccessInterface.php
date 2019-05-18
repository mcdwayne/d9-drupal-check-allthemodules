<?php

namespace Drupal\hotjar;

/**
 * Interface SnippetAccessInterface.
 *
 * @package Drupal\hotjar
 */
interface SnippetAccessInterface {

  /**
   * Determines whether we add tracking code to page.
   *
   * @return bool
   *   Return TRUE if user can access snippet.
   */
  public function check();

}
