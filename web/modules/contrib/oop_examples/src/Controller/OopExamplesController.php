<?php

/**
 * @file
 * Contains \Drupal\oop_examples\Controller\OopExamplesController.
 */

namespace Drupal\oop_examples\Controller;

/**
 * Controller routines for page example routes.
 */
class OopExamplesController {

  /**
   * Constructs a simple page.
   *
   * The router _content callback, maps the path 'oop-examples'
   * to this method.
   *
   * _content callbacks return a renderable array for the content area of the
   * page. The theme system will later render and surround the content with the
   * appropriate blocks, navigation, and styling.
   */
  public function page() {

    $message = '<p>' . t('A variety of OOP example code.') . '</p>';
    $message .= '<p>' .
      t('Examples 01-03 are applicable for Drupal 7 only and not present here, because Drupal 8 has built-in PSR-4 namespaces.') .
      '</p>';

    return array(
      '#markup' => $message,
    );
  }

}
