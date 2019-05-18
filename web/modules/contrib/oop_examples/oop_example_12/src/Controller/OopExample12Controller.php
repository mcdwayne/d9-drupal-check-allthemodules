<?php

/**
 * @file
 * Contains \Drupal\oop_example_12\Controller\OopExample12Controller.
 */

namespace Drupal\oop_example_12\Controller;

// Declare class usage.
use Drupal\oop_example_12\BusinessLogic\Common\ColorableFactory;
use Drupal\oop_example_12\BusinessLogic\Common\ColorInterface;


/**
 * Controller routines for page example routes.
 */
class OopExample12Controller {

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

    $cf = new ColorableFactory();

    $v1 = $cf->getColorable('Toyota Camry', 'black');
    $v2 = $cf->getColorable('Toyota Yaris', 'blue');

    $f1 = $cf->getColorable('Orange');
    $f2 = $cf->getColorable('Banana');

    $message = $this->colorOutput($v1);
    $message .= '<br />';
    $message .= $this->colorOutput($v2);
    $message .= '<br />';
    $message .= $this->colorOutput($f1);
    $message .= '<br />';
    $message .= $this->colorOutput($f2);
    $message .= '<br />';

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
