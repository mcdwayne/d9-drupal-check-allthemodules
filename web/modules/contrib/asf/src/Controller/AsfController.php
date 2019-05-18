<?php

namespace Drupal\asf\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for dblog routes.
 */
class AsfController extends ControllerBase {

  /**
   * A simple page to explain to the developer what to do.
   */
  public function description() {

    $entity = \Drupal\node\Entity\Node::load(1);
    //var_dump($entity->toArray());
    $entity->setPublished(0);
    $entity->save();

    return array(
      '#markup' => t(
        "The Field Example provides a field composed of an HTML RGB value, like #ff00ff. To use it, add the field to a content type."),
    );
  }

}
