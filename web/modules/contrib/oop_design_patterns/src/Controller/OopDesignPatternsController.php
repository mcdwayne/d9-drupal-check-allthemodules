<?php

/**
 * @file
 * Contains \Drupal\oop_design_patterns\Controller\OopDesignPatternsController.
 */

namespace Drupal\oop_design_patterns\Controller;

/**
 * Controller routines for page example routes.
 */
class OopDesignPatternsController {

  /**
   * Constructs a simple page.
   *
   * The router _content callback, maps the path 'oop-design-patterns'
   * to this method.
   *
   * _content callbacks return a renderable array for the content area of the
   * page. The theme system will later render and surround the content with the
   * appropriate blocks, navigation, and styling.
   */
  public function page() {

    $message = '<p>' . t('A variety of OOP Design Patterns code.') . '</p>';

    return array(
      '#markup' => $message,
    );
  }

}
