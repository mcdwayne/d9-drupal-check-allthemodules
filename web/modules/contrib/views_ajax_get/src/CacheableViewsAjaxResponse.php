<?php

namespace Drupal\views_ajax_get;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\CacheableResponseTrait;
use Drupal\views\Ajax\ViewAjaxResponse;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\Request;

class CacheableViewsAjaxResponse extends ViewAjaxResponse implements CacheableResponseInterface, \Serializable {

  use CacheableResponseTrait;

  /**
   * {@inheritdoc}
   */
  public function serialize() {
    $vars = get_object_vars($this);
    unset($vars['view']);
    $vars['view_id'] = $this->view->id();
    $vars['display_id'] = $this->view->current_display;

    return serialize($vars);
  }

  /**
   * {@inheritdoc}
   */
  public function unserialize($serialized) {
    $unserialized = unserialize($serialized);
    foreach ($unserialized as $key => $value) {
      $this->{$key} = $value;
    }
    // Ensure that there is a request on the request stack.
    $fake_request = FALSE;
    if (!$request = \Drupal::request()) {
      $fake_request = TRUE;
      \Drupal::requestStack()->push(Request::create('/uri'));
    }
    $this->view = Views::getView($unserialized['view_id']);
    $this->view->setDisplay($unserialized['display_id']);
    if ($fake_request) {
      \Drupal::requestStack()->pop();
    }
  }

}
