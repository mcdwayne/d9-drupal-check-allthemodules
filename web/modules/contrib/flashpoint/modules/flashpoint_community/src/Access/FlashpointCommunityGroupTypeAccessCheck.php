<?php

namespace Drupal\flashpoint_community\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\group\Context\GroupRouteContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Drupal\group\Access\GroupPermissionAccessCheck;
use Drupal\group\Entity\Group;

/**
 * Class FlashpointCommunityGroupTypeAccessCheck
 * @package Drupal\flashpoint_community\Access
 */
class FlashpointCommunityGroupTypeAccessCheck implements AccessInterface {

  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $community = $route_match->getParameter('group');
    if (is_string($community)) {
      $group = Group::load($community);
      if ($group->bundle() === 'flashpoint_community') {
        return AccessResult::allowed();
      }
    }
    elseif ($community->bundle() === 'flashpoint_community') {
      return AccessResult::allowed();
    }
    // If this isn't a community, then we should hide/deny access to this route.
    return AccessResult::forbidden();
  }
}