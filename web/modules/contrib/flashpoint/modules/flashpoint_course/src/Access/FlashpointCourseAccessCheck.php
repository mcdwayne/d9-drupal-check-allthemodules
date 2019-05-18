<?php

namespace Drupal\flashpoint_course\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Drupal\flashpoint_course\FlashpointCourseUtilities;


class FlashpointCourseAccessCheck implements AccessInterface {

  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $course = $route_match->getParameter('group');
    if ($course && $course->get('type')->getValue()[0]['target_id'] === 'course') {
      return AccessResult::allowedIf(FlashpointCourseUtilities::enrollAccess($course, $account));
    }
    return AccessResult::allowed();
  }
}