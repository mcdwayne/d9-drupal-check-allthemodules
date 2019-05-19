<?php

namespace Drupal\slides_presentation\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for page presentation route.
 */
class SlidesPresentationController extends ControllerBase {

  /**
   * Rendering the content.
   */
  public function content($id_presentation) {

    return [
      '#theme' => 'page__slides',
    ];

  }

}
