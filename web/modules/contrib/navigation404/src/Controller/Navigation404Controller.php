<?php
/**
 * @file
 * Contains \Drupal\navigation404\Controller\Navigation404Controller.
 */

namespace Drupal\navigation404\Controller;
use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for navigation404.module.
 */
class Navigation404Controller extends ControllerBase {
  /**
   * Returns "Not found" text.
   */
  public function notFound() {
    return array(
      '#markup' => t('The requested page could not be found.')
    );
  }
}
