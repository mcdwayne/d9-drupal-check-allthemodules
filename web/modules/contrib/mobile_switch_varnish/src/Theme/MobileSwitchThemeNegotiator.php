<?php

/**
 * @file
 * Contains \Drupal\mobile_switch_varnish\Theme\ThemeNegotiator.
 */

namespace Drupal\mobile_switch_varnish\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Sets the active theme on admin pages.
 */
class MobileSwitchThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The route admin context to determine whether a route is an admin one.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * Creates a new AdminNegotiator instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The route admin context service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AdminContext $admin_context) {
    $this->configFactory = $config_factory;
    $this->adminContext = $admin_context;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    // Detect if this is a mobile device.
    if (($device = \Drupal::request()->server->get('HTTP_X_DEVICE')) && strstr($device, 'mobile')) {
      // Bail if this is an admin page and we don't respect admin pages.
      if (!$this->configFactory->get('mobile_switch_varnish.settings')->get('use_on_admin_pages') && $this->adminContext->isAdminRoute($route_match->getRouteObject())) {
        return FALSE;
      }
      // Else yes return TRUE; we definitely want this to apply.
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    // Fetch the device type from the server variable.
    $device_type = substr(\Drupal::request()->server->get('HTTP_X_DEVICE'), 7);
    // If the mobile theme for device is empty (unlikely) or set to 'default' then return the default mobile theme.
    if (!$this->configFactory->get('mobile_switch_varnish.settings')->get('theme.' . $device_type) || $this->configFactory->get('mobile_switch_varnish.settings')->get('theme.' . $device_type) == 'default') {
      return $this->configFactory->get('mobile_switch_varnish.settings')->get('theme.default');
    }
    // Else it's been set specifically; use this theme setting instead.
    return $this->configFactory->get('mobile_switch_varnish.settings')->get('theme.' . $device_type);
  }

}
