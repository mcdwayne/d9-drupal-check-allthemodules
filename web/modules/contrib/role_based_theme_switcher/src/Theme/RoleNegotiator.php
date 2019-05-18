<?php

namespace Drupal\role_based_theme_switcher\Theme;

use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxy;

/**
 * Sets the active theme on admin pages.
 */
class RoleNegotiator implements ThemeNegotiatorInterface {

  /**
   * Protected configFactory variable.
   *
   * @var configFactory
   */
  protected $configFactory;

  /**
   * Protected adminRoute variable.
   *
   * @var adminRoute
   */
  protected $adminRoute;

  /**
   * Protected route_match variable.
   *
   * @var route_match
   */
  protected $routeMatch;

  /**
   * Protected account variable.
   *
   * @var account
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, AdminContext $adminRoute, RouteMatchInterface $routeMatch, AccountProxy $account) {
    $this->configFactory = $config_factory;
    $this->adminRoute = $adminRoute;
    $this->routeMatch = $routeMatch;
    $this->account = $account;
  }

  /**
   * Whether this theme negotiator should be used to set the theme.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   *
   * @return bool
   *   TRUE if this negotiator should be used or FALSE to let other negotiators
   *   decide.
   */
  public function applies(RouteMatchInterface $route_match) {
    // Use this theme on a certain route.
    $change_theme = TRUE;
    $route = $this->routeMatch->getRouteObject();
    $is_admin_route = $this->adminRoute->isAdminRoute($route);
    $user_roles = $this->account->getRoles();
    $has_admin_role = FALSE;
    if (in_array("administrator", $user_roles)) {
      $has_admin_role = TRUE;
    }
    if ($is_admin_route === TRUE && $has_admin_role === TRUE) {
      $change_theme = FALSE;
    }
    // Here you return the actual theme name.
    $roleThemes = $this->configFactory->get('role_based_theme_switcher.RoleBasedThemeSwitchConfig')->get('roletheme');

    // Get current roles a user has.
    $roles = $this->account->getRoles();
    // Get highest role.
    $theme_role = $this->getPriorityRole($roles);
    $this->theme = $roleThemes[$theme_role]['id'];

    return $change_theme;
  }

  /**
   * Determine the active theme for the request.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match object.
   *
   * @return string|null
   *   The name of the theme, or NULL if other negotiators, like the configured
   *   default one, should be used instead.
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->theme;
  }

  /**
   * Function to get roles array and return highest priority role.
   *
   * @param array $roles
   *   Array of roles.
   *
   * @return string
   *   Return role.
   */
  public function getPriorityRole(array $roles) {
    $themes = $this->configFactory->get('role_based_theme_switcher.RoleBasedThemeSwitchConfig')->get('roletheme');
    if (isset($themes)) {
      foreach ($themes as $key => $value) {
        if (in_array($key, $roles)) {
          $themeArr[$key] = $value['weight'];
        }
      }
      $priRole = array_search(max($themeArr), $themeArr);
      // Return role.
      return $priRole;
    }
  }

}
