<?php

namespace Drupal\idna\Controller;

/**
 * @file
 * Contains \Drupal\idna\Controller\Page.
 */

use Drupal\Core\Controller\ControllerBase;

/**
 * Page controller.
 */
class Page extends ControllerBase {

  /**
   * Demo page.
   */
  public function demo() {
    $output = "";
    return [
      'text' => ['#markup' => $output],
      'encode' => \Drupal::formBuilder()->getForm('\Drupal\idna\Form\Encode'),
      'decode' => \Drupal::formBuilder()->getForm('\Drupal\idna\Form\Decode'),
    ];
  }

}
