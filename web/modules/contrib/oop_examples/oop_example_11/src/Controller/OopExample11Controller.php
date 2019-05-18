<?php

/**
 * @file
 * Contains \Drupal\oop_example_11\Controller\OopExample11Controller.
 */

namespace Drupal\oop_example_11\Controller;

// Declare class usage.
use Drupal\oop_example_11\BusinessLogic\Common\ColorInterface;
use Drupal\oop_example_11\BusinessLogic\Driver\Driver;
use Drupal\oop_example_11\BusinessLogic\Vehicle\Car\Toyota\ToyotaCamry;


/**
 * Controller routines for page example routes.
 */
class OopExample11Controller {

  /**
   * Constructs a simple page.
   *
   * The router _content callback, maps the path 'oop-examples/oop-example-04'
   * to this method.
   *
   * _content callbacks return a renderable array for the content area of the
   * page. The theme system will later render and surround the content with the
   * appropriate blocks, navigation, and styling.
   */
  public function page() {

    $v = new ToyotaCamry();
    $v->color = t('black');
    $message = $v->getDescription();
    $message .= '<br />';
    $message .= $v->getDoorsDescription();
    $message .= '<br />';

    $message .= $this->colorOutput($v);
    $message .= '<br />';
    $message .= '<br />';

    $d = new Driver('John', $v);

    $directions1 = array(
      1,
      'Left',
      1.5,
    );

    $message .= $d->driveByDirections($directions1);
    $message .= '<br />';

    $directions2 = array(
      2,
      'Left',
      3.3,
      'Right',
      2.5,
    );

    $message .= $d->driveByDirections($directions2);

    return array(
      '#markup' => '<p>' . $message . '</p>',
    );
  }

  /**
   * Returns color message.
   */
  protected function colorOutput(ColorInterface $color_object) {
    $s = t('This object has color') . ' ' . $color_object->getColor() . '.';
    return $s;
  }

}
