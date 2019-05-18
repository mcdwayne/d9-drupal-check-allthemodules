<?php

namespace Drupal\menu_per_user\AccessChecks;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResultAllowed;
use Symfony\Component\Routing\Route;
use Drupal\Core\Routing\RouteMatchInterface;



class MenuAccessChecks implements AccessInterface {


  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $uid = \Drupal::currentUser()->id();
    $access_check = menu_per_user_check_path_uid($route->getPath(), $uid);
    if(!$access_check) {
      $access_check = menu_per_user_check_path_uid(substr($route->getPath(), 1), $uid);
    }
    if($access_check) {
      return AccessResultAllowed::allowedIf($access_check === 1);
    }
    else{
      return AccessResultAllowed::allowedIf($access_check != 1);
    }
    
  }
}
