<?php

/**
 * @file
 * Contains \Drupal\itchio_field\Controller\FieldExampleController.
 */

namespace Drupal\itchio_field\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for dblog routes.
 */
class ItchioFieldController extends ControllerBase {

  /**
   * A simple page to explain to the developer what to do.
   */
  public function description() {
    return [
      '#markup' => t(
        "Provides a field to enter an Itch.io project number and display an iframe widget."),
    ];
  }

}
