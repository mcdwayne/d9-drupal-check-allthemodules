<?php /**
 * @file
 * Contains \Drupal\writeup\Controller\WriteupController.
 */

namespace Drupal\writeup\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Default controller for the writeup module.
 */
class WriteupController extends ControllerBase {

  public function writeup_status_page() {
    $build = array(
      '#markup' => $this->t('<h2>This page is not yet implemented for Drupal 8</h2>'),
    );
    return $build;
  }

}
