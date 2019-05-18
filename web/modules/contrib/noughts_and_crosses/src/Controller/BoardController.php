<?php

/**
 * @file
 * Contains \Drupal\noughts_and_crosses\Controller\BoardController.
 */

namespace Drupal\noughts_and_crosses\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Noughts and Crosses module routes.
 */
class BoardController extends ControllerBase {

  /**
   * The form class name.
   *
   * @var \Drupal\noughts_and_crosses\Form\Board\BoardStepOneForm
   */
  private $form_class = '\Drupal\noughts_and_crosses\Form\Board\BoardStepOneForm';

  /**
   * Displays the Noughts and Crosses showBoard page.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function showBoard() {
    
    $build = [
      'form' => \Drupal::formBuilder()->getForm($this->form_class),
    ];
    return $build;
  }
}
