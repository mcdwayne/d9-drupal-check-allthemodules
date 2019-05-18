<?php
namespace Drupal\menu_custom_access\AccessChecks;

use Drupal\Core\Link;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

use Symfony\Component\Routing\Route;

class RouteAccessChecks implements AccessInterface {

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    // Get the config settings
    $config = \Drupal::config('menu_custom_access.settings');

    // Restrict add menu access   
    if($route_match->getRouteName() == 'entity.menu.add_form' && !empty($config->get('menu_custom_access.roles'))) {
      return AccessResult::forbidden();
    }

    // Get a list of the restricted routes from config
    $config_route_paths = $config->get('menu_custom_access.routes');
    $route_paths = preg_split('/\r\n|\r|\n/', $config_route_paths);
    $config_roles = $config->get('menu_custom_access.roles');
    $account_has_role = array_intersect($account->getRoles(), $config_roles);

    // Restrict access to paths that are set in config
    if(in_array($route->getPath(), $route_paths)) {
      // Allow access to roles specified in config
      return AccessResultAllowed::allowedIf(
        !empty($account_has_role)
      );
    } 

    // Output route path debug
    if(!empty($config->get('menu_custom_access.route_debug'))) {
      $messenger = \Drupal::messenger();
      $messenger->addMessage(
        t("Menu Custom Access route path(s) used on this page: @path",
          array('@path'=> $route->getPath())
        ),
        $messenger::TYPE_STATUS
      );
    }    
    
    return AccessResult::allowed();
  }
}