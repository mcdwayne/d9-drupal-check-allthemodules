<?php

namespace Drupal\akismet_test;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatch;
use Symfony\Component\HttpFoundation\Request;

/**
 * Contains basic page callbacks for the akismet test module.
 */
class TestController extends ControllerBase  {

  public function resetViewCount(Request $request) {
    \Drupal::state()->delete('akismet_test.view_count');

    $route_match = $route = RouteMatch::createFromRequest($request);
    return $this->redirect($route_match->getRouteName(), $route_match->getRawParameters()->all());
  }
}
