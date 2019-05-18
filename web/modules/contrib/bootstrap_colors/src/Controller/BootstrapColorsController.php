<?php

/**
 * @file
 * Contains \Drupal\bootstrap_colors\Controller\BootstrapColorsController.
 */

namespace Drupal\bootstrap_colors\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for page example routes.
 */
class BootstrapColorsController extends ControllerBase {

  /**
   * Returns a full HTML from a twig template, no arguments.
   *
   */
  public function content() {
    $form = \Drupal::formBuilder()->getForm('Drupal\bootstrap_colors\Form\ColorForm');
    $render_array['bootstrap_colors_content'] = array(
      // The theme function to apply to the #items
      '#theme' => 'bootstrap_colors',
      '#title' => $this->t('Bootstrap Color Scheme Generator'),
      '#form' => \Drupal::service('renderer')->render($form),
    );
    return $render_array;
  }

}
