<?php

namespace Drupal\route_basic_auth\Config;

use Drupal\Core\Config\ConfigFactory;

/**
 * Manages module configuration.
 *
 * @package Drupal\route_basic_auth\Configuration
 */
class ConfigManager {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * ConfigManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The config factory service.
   */
  public function __construct(ConfigFactory $configFactory) {
    $this->config = $configFactory->get('route_basic_auth.settings');
  }

  /**
   * Returns the configured credential username.
   *
   * @return string
   *   The username.
   */
  public function getUsername() {
    return $this->config->get('credentials.username');
  }

  /**
   * Returns the configured credential password.
   *
   * @return string
   *   The password.
   */
  public function getPassword() {
    return $this->config->get('credentials.password');
  }

  /**
   * Gets the information of the routes that should be protected.
   *
   * @return array[\Drupal\route_basic_auth\Config\ProtectedRouteConfig]
   *   Protected route configurations.
   */
  public function getProtectedRoutes() {
    $protectedRoutes = [];
    $protectedRoutesConfigData = $this->config->get('protected_routes');

    /* Do not create array of ProtectedRouteConfig objects if no protected routes are configured. */
    if (is_array($protectedRoutesConfigData)) {
      foreach ($protectedRoutesConfigData as $protectedRouteConfigData) {
        $protectedRouteConfig = new ProtectedRouteConfig($protectedRouteConfigData['name'], $protectedRouteConfigData['methods']);
        $protectedRoutes[] = $protectedRouteConfig;
      }
    }

    return $protectedRoutes;
  }

  /**
   * Gets the information of the route with given name that should be protected.
   *
   * @return \Drupal\route_basic_auth\Config\ProtectedRouteConfig|null
   *   Protected route configuration.
   *   NULL if no route that should be protected with given name was found.
   */
  public function getProtectedRoute($name) {
    $matchingProtectedRoute = NULL;
    $protectedRoutes = $this->getProtectedRoutes();

    foreach ($protectedRoutes as $protectedRoute) {
      if ($protectedRoute->getName() === $name) {
        $matchingProtectedRoute = $protectedRoute;
        break;
      }
    }

    return $matchingProtectedRoute;
  }

  /**
   * Gets the names of all routes that should be protected.
   *
   * @return array[string]
   *   Array with route names.
   */
  public function getProtectedRouteNames() {
    $protectedRouteNames = [];
    $protectedRoutes = $this->getProtectedRoutes();

    foreach ($protectedRoutes as $protectedRoute) {
      $protectedRouteNames[] = $protectedRoute->getName();
    }

    return $protectedRouteNames;
  }

  /**
   * Whether or not the route with given name should be protected.
   *
   * @param string $routeName
   *   A route name.
   *
   * @return bool
   *   Should route with given name be protected.
   */
  public function shouldRouteBeProtected($routeName) {
    return in_array($routeName, $this->getProtectedRouteNames());
  }

  /**
   * Checks if the flood protections is configured as enabled.
   *
   * @return bool
   *   TRUE if flood protection is enabled.
   */
  public function isFloodProtectionEnabled() {
    $configValue = $this->config->get('flood_protection_enabled');

    return boolval($configValue);
  }

}
