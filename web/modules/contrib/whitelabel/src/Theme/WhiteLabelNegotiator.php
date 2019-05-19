<?php

namespace Drupal\whitelabel\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\whitelabel\WhiteLabelProviderInterface;

/**
 * Class WhiteLabelNegotiator.
 *
 * Registers the configured white label theme as the active theme.
 *
 * @package Drupal\whitelabel\Theme
 */
class WhiteLabelNegotiator implements ThemeNegotiatorInterface {

  /**
   * Holds the white label.
   *
   * @var \Drupal\whitelabel\WhiteLabelProviderInterface
   */
  protected $whiteLabelProvider;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * WhiteLabelNegotiator constructor.
   *
   * @param \Drupal\whitelabel\WhiteLabelProviderInterface $white_label_provider
   *   The white label provider.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(WhiteLabelProviderInterface $white_label_provider, ConfigFactoryInterface $config_factory) {
    $this->whiteLabelProvider = $white_label_provider;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    // Only apply if there is an active white label.
    if ($this->whiteLabelProvider->getWhiteLabel()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    $admin_theme = $this->configFactory->get('whitelabel.settings')->get('site_admin_theme');
    $allow_overrides = $this->configFactory->get('whitelabel.settings')->get('site_theme');

    $theme = $this->whiteLabelProvider->getWhiteLabel()->getTheme();

    // If users have permissions to set their own themes.
    if ($allow_overrides && !empty($theme)) {
      // Return the theme configured for the white label.
      return $theme;
    }
    elseif (!empty($admin_theme)) {
      // If a global default was set, use that.
      return $admin_theme;
    }

    // No user specific config and no global setting.
    // Allow other negotiators to resolve this.
    return NULL;
  }

}
