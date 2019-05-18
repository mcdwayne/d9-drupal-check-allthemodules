<?php

namespace Drupal\dark_mode\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;

/**
 * Class darkModeThemeSwitchService.
 */
class DarkModeThemeSwitchService implements ThemeNegotiatorInterface {

  /**
   * Protected theme variable to store the theme to active.
   *
   * @var string
   */
  protected $theme = NULL;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The route admin context to determine whether a route is an admin one.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, AdminContext $admin_context, ThemeHandlerInterface $theme_handler) {
    $this->configFactory = $config_factory;
    $this->adminContext = $admin_context;
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {

    $themes         = $this->getThemeList();
    $selected_theme = $this->configFactory->getEditable('dark_mode.adminsetting')->get('active_theme');

    if (!empty($selected_theme) && $selected_theme != '__none') {

      $start_time   = strtotime($themes[$selected_theme]['start_time']);
      $end_time     = strtotime($themes[$selected_theme]['end_time']);
      $current_time = time();
      if ($start_time - $current_time < 0 && $current_time - $end_time < 0) {
        $this->theme = $this->configFactory->getEditable('dark_mode.adminsetting')->get('active_theme');
      }
    }
    return ($this->theme && !$this->adminContext->isAdminRoute($route_match->getRouteObject()));
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->theme;
  }

  /**
   * Gets a list of active themes without hidden ones.
   *
   * @return array[]
   *   An array with all compatible active themes.
   */
  private function getThemeList() {
    $config = $this->configFactory->getEditable('dark_mode.adminsetting')->get('dark_mode');

    $themes_list = [];
    $themes      = $this->themeHandler->listInfo();
    foreach ($themes as $theme) {
      $theme_name = $theme->getName();
      if (!empty($theme->info['hidden'])) {
        continue;
      }
      $themes_list[$theme_name] = [
        'theme_name' => $theme->info['name'],
        'start_time' => ($config[$theme_name]['start_time']) ? $config[$theme_name]['start_time'] : "",
        'end_time' => ($config[$theme_name]['end_time']) ? $config[$theme_name]['end_time'] : "",
      ];
    }
    return $themes_list;
  }

}
