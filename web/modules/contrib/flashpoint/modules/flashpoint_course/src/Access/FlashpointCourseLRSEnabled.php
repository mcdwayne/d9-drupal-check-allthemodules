<?php

namespace Drupal\flashpoint_course\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;


class FlashpointCourseLRSEnabled implements AccessInterface {

  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('flashpoint_lrs_client')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }
}