<?php

namespace Drupal\icon_field\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for dblog routes.
 */
class IconFieldController extends ControllerBase {

  /**
   * A simple page to explain to the developer what to do.
   */
  public function description() {
    return array(
      '#markup' => t(
        "Lets you add icons to any piece of content. To use it, add the field to a content type."),
    );
  }

}
