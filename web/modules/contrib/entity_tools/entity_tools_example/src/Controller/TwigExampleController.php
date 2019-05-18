<?php

namespace Drupal\entity_tools_example\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class TwigExampleController.
 */
class TwigExampleController extends ControllerBase {

  /**
   * Index.
   *
   * @return string
   *   Twig Example template.
   */
  public function index() {
    return [
      '#theme' => 'twig_example',
    ];
  }

}
