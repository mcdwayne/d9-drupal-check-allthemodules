<?php

/*
 * @file
 * Contains \Drupal\node_display_title\Controller\ModeDisplayTitleController.
 */

namespace Drupal\node_display_title\Controller;

use Drupal\Core\Controller\ControllerBase;

class NodeDisplayTitleController extends ControllerBase {
  
  /**
   * {@inheritdoc}
   */
  public function content() {
    $build = array(
      '#type' => 'markup',
      '#markup' => t('Hello World!'),
    );
    return $build;
  }
  
}
