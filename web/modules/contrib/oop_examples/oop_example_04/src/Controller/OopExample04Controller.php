<?php

/**
 * @file
 * Contains \Drupal\page_example\Controller\PageExampleController.
 */

namespace Drupal\oop_example_04\Controller;

// Declare class usage.
use Drupal\oop_example_04\Classes\Vehicle;
use Drupal\oop_example_04\Classes\Car;
use Drupal\oop_example_04\Classes\Motorcycle;


/**
 * Controller routines for page example routes.
 */
class OopExample04Controller {

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

    $v = new Vehicle();
    $message = $v->getDescription();
    $message .= '<br />';

    $v1 = new Car();
    $message .= $v1->getDescription();
    $message .= '<br />';

    $v2 = new Motorcycle();
    $message .= $v2->getDescription();
    $message .= '<br />';

    return array(
      '#markup' => '<p>' . $message . '</p>',
    );
  }

}
