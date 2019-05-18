<?php
/**
 * @file
 * Contains \Drupal\kalastatic\Controller\KalastaticAdmin.
 */

namespace Drupal\kalastatic\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

class KalastaticAdmin extends ControllerBase {
  public function content() {
    return array(
      '#type' => 'markup',
      '#markup' => $this->t('Kalastatic, bitches!'),
    );
  }
}
