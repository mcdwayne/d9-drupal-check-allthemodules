<?php

namespace Drupal\owntracks\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Access\AccessResult;

/**
 * OwnTracksUserMapAccess definition.
 */
class OwnTracksUserMapAccess implements AccessInterface {

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * OwnTracksUserMapAccess constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match service.
   */
  public function __construct(CurrentRouteMatch $current_route_match) {
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * Check access to owntracks user map.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in user.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Allowed or Neutral.
   */
  public function access(AccountInterface $account) {
    $user = $this->currentRouteMatch->getParameter('user');

    if ($user instanceof UserInterface) {
      $uid = $user->id();
    }
    else {
      $uid = $user;
    }

    $access = AccessResult::allowedIfHasPermissions($account, ['administer owntracks', 'view any owntracks entity'], 'OR');

    if (!$access->isAllowed() && $account->id() == $uid && !empty($uid)) {
      return $access->orIf(AccessResult::allowedIfHasPermission($account, 'view own owntracks entities')
        ->cachePerUser());
    }

    return $access;
  }

}
