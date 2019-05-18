<?php

namespace Drupal\backstop_generator\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for displaying twig template with backstop.json file.
 */
class BackstopConfigurationView extends ControllerBase {

  /**
   * Returns a renderable array for the page.
   */
  public function content() {
    $build = [
      '#theme' => 'backstop_configuration_view',
      '#markup' => t('Your configuration:'),
      '#attached' =>
        [
          'library' =>
            ['backstop_generator/prettyprint'],
        ],
      '#cache' => [
        'max_age' => 0,
      ],
    ];
    return $build;
  }

}
