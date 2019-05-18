<?php

/**
 * @file
 * Contains \Drupal\adapter_oop_design_pattern\Controller\OopDesignPattern1Controller.
 */

namespace Drupal\adapter_oop_design_pattern\Controller;

// Declare class usage.
use Drupal\adapter_oop_design_pattern\Module\Log\Log;
use Drupal\adapter_oop_design_pattern\Module\Wrappers\PaymentWrapper1;
use Drupal\adapter_oop_design_pattern\Module\Wrappers\PaymentWrapper2;
use Drupal\adapter_oop_design_pattern\Module\Wrappers\PaymentWrapper3;


/**
 * Controller routines for page example routes.
 */
class AdapterOopDesignPatternController {

  /**
   * Constructs a simple page.
   *
   * The router _content callback, maps the path
   * 'oop-design-patterns/oop-design-pattern-1'
   * to this method.
   *
   * _content callbacks return a renderable array for the content area of the
   * page. The theme system will later render and surround the content with the
   * appropriate blocks, navigation, and styling.
   */
  public function page() {

    $message = 'Start';
    $message .= '<br>';

    Log::start();
    // Just add one more line.
    Log::write('');

    $wrapper1 = new PaymentWrapper1();
    $wrapper1->doPayment('5454545454545454', '12/18', '123');
    // Just add one more line.
    Log::write('');

    $wrapper2 = new PaymentWrapper2();
    $wrapper2->doPayment('5454545454545454', '12/18', '123');
    // Just add one more line.
    Log::write('');

    $wrapper3 = new PaymentWrapper3();
    $wrapper3->doPayment('5454545454545454', '12/18', '123');
    // Just add one more line.
    Log::write('');

    $message .= Log::flush();
    return array(
      '#markup' => '<p>' . $message . '</p>',
    );
  }

}
