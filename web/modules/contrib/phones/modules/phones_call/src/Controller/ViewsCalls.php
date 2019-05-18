<?php

namespace Drupal\phones_call\Controller;

/**
 * @file
 * Contains \Drupal\phones_call\Controller\ViewsCalls.
 */

use Drupal\Core\Controller\ControllerBase;
use Drupal\views\Views;

/**
 * Controller ViewsCalls.
 */
class ViewsCalls extends ControllerBase {

  /**
   * Get Renderale.
   */
  public static function getRenderable($phones) {
    $view = Views::getView('phones_calls');
    $renderable = [];
    if (is_object($view) && !empty($phones)) {
      $args = [implode('+', $phones)];
      $view->setArguments($args);
      $view->setDisplay('block');
      $view->preExecute();
      $view->execute();
      $renderable = $view->buildRenderable('block', $args);
    }
    return $renderable;
  }

}
