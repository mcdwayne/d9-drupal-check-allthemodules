<?php

namespace Drupal\flashpoint_community\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Drupal\flashpoint_community\FlashpointCommunityUtilities;


class FlashpointCommunityAccessCheck implements AccessInterface {

  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $community = $route_match->getParameter('group');
    if ($community && $community->get('type')->getValue()[0]['target_id'] === 'flashpoint_community') {
      return AccessResult::allowedIf(FlashpointCommunityUtilities::joinAccess($community, $account));
    }
    // If this isn't a Flashpoint community, this check doesn't apply.
    return AccessResult::allowed();
  }
}