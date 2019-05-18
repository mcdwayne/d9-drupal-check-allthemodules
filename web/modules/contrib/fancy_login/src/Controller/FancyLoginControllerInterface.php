<?php

namespace Drupal\fancy_login\Controller;

/**
 * Interface for the ajax controller for the fancy login module.
 */
interface FancyLoginControllerInterface {

  /**
   * Provides the ajax callback response for the Fancy Login module.
   */
  public function ajaxCallback($type);

}
