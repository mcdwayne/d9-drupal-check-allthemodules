<?php

/**
 * @file
 * Contains \Drupal\oop_example_08\Controller\OopExample08Controller.
 */

namespace Drupal\oop_example_08\Controller;

// Declare class usage.
use Drupal\oop_example_08\BusinessLogic\Common\ColorInterface;
use Drupal\oop_example_08\BusinessLogic\Vehicle\Vehicle;
use Drupal\oop_example_08\BusinessLogic\Vehicle\Car\Car;
use Drupal\oop_example_08\BusinessLogic\Vehicle\Car\Toyota\ToyotaCamry;
use Drupal\oop_example_08\BusinessLogic\Vehicle\Car\Toyota\ToyotaYaris;
use Drupal\oop_example_08\BusinessLogic\Vehicle\Motorcycle\Motorcycle;
use Drupal\oop_example_08\BusinessLogic\Fruit\Orange;
use Drupal\oop_example_08\BusinessLogic\Fruit\Banana;


/**
 * Controller routines for page example routes.
 */
class OopExample08Controller {

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
    $v1->color = t('blue');
    $message .= $v1->getDescription();
    $message .= '<br />';

    $v2 = new Motorcycle();
    $v2->color = t('green');
    $message .= $v2->getDescription();
    $message .= '<br />';

    $v3 = new ToyotaCamry();
    $v3->color = t('black');
    $message .= $v3->getDescription();
    $message .= '<br />';
    $message .= $v3->getDoorsDescription();
    $message .= '<br />';

    $v4 = new ToyotaYaris();
    $v4->color = t('yellow');
    $message .= $v4->getDescription();
    $message .= '<br />';
    $message .= $v4->getDoorsDescription();
    $message .= '<br />';

    $f1 = new Orange();
    $f2 = new Banana();

    $message .= $this->colorOutput($f1);
    $message .= '<br />';
    $message .= $this->colorOutput($v3);
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
