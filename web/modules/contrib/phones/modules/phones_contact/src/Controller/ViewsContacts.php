<?php

namespace Drupal\phones_contact\Controller;

/**
 * @file
 * Contains \Drupal\phones_contact\Controller\ViewsContacts.
 */

use Drupal\Core\Controller\ControllerBase;
use Drupal\views\Views;

/**
 * Controller ViewsContacts.
 */
class ViewsContacts extends ControllerBase {

  /**
   * Get Renderale.
   */
  public static function getRenderable($entity) {
    $view = Views::getView('phones_contacts');
    $renderable = [];
    if (is_object($view)) {
      $args = [$entity->id()];
      $view->setArguments($args);
      $view->setDisplay('block');
      $view->preExecute();
      $view->execute();
      $renderable = $view->buildRenderable('block', $args);
    }
    return $renderable;
  }

}
