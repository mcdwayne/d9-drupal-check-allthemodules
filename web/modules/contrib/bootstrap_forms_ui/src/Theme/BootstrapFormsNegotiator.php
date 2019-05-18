<?php

namespace Drupal\bootstrap_forms_ui\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Class BootstrapFormsNegotiator.
 *
 * @package Drupal\bootstrap_forms_ui\Theme
 */
class BootstrapFormsNegotiator implements ThemeNegotiatorInterface {

  /**
   * Creates a new AdminNegotiator instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    // Use this theme on a certain route.
    $rm = $route_match->getRouteName();
    return in_array($rm, array(
      'bootstrap_forms.elements_test',
      'bootstrap_forms.elements_test_theme',
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {

    // Find the theme that was passed in as a parameter.
    $theme = $route_match->getParameter('theme');

    // If there is no theme then return the default.
    if (!$theme) {
      $theme = $this->configFactory->get('system.theme')->get('default');
    }
    return $theme;
  }

}

