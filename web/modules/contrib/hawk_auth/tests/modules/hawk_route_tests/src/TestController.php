<?php

/**
 * @file
 * Contains \Drupal\router_test\TestControllers.
 */

namespace Drupal\hawk_route_tests;

/**
 * Controller routines for testing the routing system.
 */
class TestController {

  public function user() {
    return ['#markup' => \Drupal::currentUser()->getUsername()];
  }

}
