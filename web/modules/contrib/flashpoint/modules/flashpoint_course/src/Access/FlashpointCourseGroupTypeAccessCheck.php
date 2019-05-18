<?php

namespace Drupal\flashpoint_course\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\Routing\Route;
use Drupal\group\Entity\Group;

class FlashpointCourseGroupTypeAccessCheck implements AccessInterface {

  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $course = $route_match->getParameter('group');
    if (is_numeric($course)) {
      $group = Group::load($course);
      if ($group->bundle() === 'course') {
        return AccessResult::allowed();
      }
    }
    elseif ($course->bundle() === 'course') {
      return AccessResult::allowed();
    }
    // If this isn't a course, then we should hide/deny access to this route.
    return AccessResult::forbidden();
  }
}