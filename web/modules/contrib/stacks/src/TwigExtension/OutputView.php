<?php

namespace Drupal\stacks\TwigExtension;

use Drupal\views\Views;

/**
 * Class OutputView.
 * @package Drupal\stacks\TwigExtension
 */
class OutputView extends \Twig_Extension {

  /**
   * Generates a list of all Twig filters that this extension defines.
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('getView', [$this, 'getView']),
    ];
  }

  /**
   * Gets a unique identifier for this Twig extension.
   */
  public function getName() {
    return 'stacks_getview.twig_extension';
  }

  /**
   * Takes a uri object and image style string, and return the image html. If
   * an image style string is not specified, use the original url. Also takes
   * a classes string that is attached to the <img> tag.
   */
  public static function getView($view_name, $display_id, $view_options) {

    $view = Views::getView($view_name);
    if (!$view || !$view->access($display_id)) {
      return;
    }

    $args = [];
    return $view->preview($display_id, $args);
  }

}
