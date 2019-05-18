<?php

namespace Drupal\form_mode_routing\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\Routing\Route;
use Drupal\Core\Routing\RouteMatchInterface;

/**
* Checks access for displaying form mode control.
*/
class CustomAccessCheck implements AccessInterface {

  public $route_match;

 /**
  * A custom access check.
  *
  * @param \Drupal\Core\Session\AccountInterface $account
  *   Run access checks for this account.
  */
  public function access(AccountInterface $account, RouteMatchInterface $route_match = NULL) {
    if (!empty($route_match)) {
      $route_name = $route_match->getRouteName();
      $explode = explode('form_mode_routing.', $route_name);
      if (!empty($explode[1])) {
        $modes = \Drupal::entityTypeManager()->getStorage('form_routing_entity')->loadByProperties([
          'label' => $explode[1]
        ]);
        if (count($modes) == 1) {
          $mode = reset($modes);
          $access = $mode->getAccess();
          $roles = $account->getRoles();
          foreach($access as $role) {
            if (in_array($role, $roles)) {
              return AccessResult::allowed();
            }
          }
        }
      }
    }
    return AccessResult::forbidden();
  }

}
