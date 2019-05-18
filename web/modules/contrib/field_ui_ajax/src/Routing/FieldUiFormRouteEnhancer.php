<?php

/**
 * @file
 * Contains \Drupal\field_ui_ajax\Routing\FieldUiFormRouteEnhancer.
 */

namespace Drupal\field_ui_ajax\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Drupal\Core\Routing\Enhancer\RouteEnhancerInterface;

/**
 * Enhancer to add a wrapping controller for _field_ui_form routes.
 */
class FieldUiFormRouteEnhancer implements RouteEnhancerInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return $route->hasDefault('_field_ui_form') && !$route->hasDefault('_controller');
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    $defaults['_controller'] = 'controller.field_ui_form:' . $defaults['_method'];
    return $defaults;
  }

}
