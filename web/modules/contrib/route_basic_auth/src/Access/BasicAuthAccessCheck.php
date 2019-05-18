<?php

namespace Drupal\route_basic_auth\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\route_basic_auth\Config\ConfigManager;
use Drupal\route_basic_auth\Config\ProtectedRouteConfig;
use Drupal\route_basic_auth\Routing\RouteHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Checks if HTTP basic authentication credentials are given and correct.
 */
class BasicAuthAccessCheck implements AccessInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  private $request;

  /**
   * The config manager service.
   *
   * @var \Drupal\route_basic_auth\Config\ConfigManager
   */
  private $configManager;

  /**
   * The route helper service.
   *
   * @var \Drupal\route_basic_auth\Routing\RouteHelper
   */
  private $routeHelper;

  /**
   * BasicAuthAccessCheck constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The current request stack.
   * @param \Drupal\route_basic_auth\Config\ConfigManager $configManager
   *   The config factory.
   * @param \Drupal\route_basic_auth\Routing\RouteHelper $routeHelper
   *   The route helper service.
   */
  public function __construct(RequestStack $requestStack, ConfigManager $configManager, RouteHelper $routeHelper) {
    $this->request = $requestStack->getCurrentRequest();
    $this->configManager = $configManager;
    $this->routeHelper = $routeHelper;
  }

  /**
   * Checks if HTTP basic authentication credentials are given and correct.
   *
   * And if they match the configured credentials.
   */
  public function access() {
    $routeName = $this->routeHelper->getRouteNameFromRequest($this->request);
    if ($routeName === NULL) {
      // Abort if the current request does not match a route.
      return AccessResult::allowed();
    }

    /* Do not run access checks if the current route should not be protected */
    $protectedRoute = $this->configManager->getProtectedRoute($routeName);
    if ($protectedRoute instanceof  ProtectedRouteConfig) {
      /* Skip checking of basic auth credentials if current request method should not be protected. */
      if (!$protectedRoute->shouldMethodBeProtected($this->request->getMethod())) {
        return AccessResult::allowed();
      }

      $basicAuthCredentialsValid = $this->checkIfBasicAuthCredentialsAreValid($this->request);

      if ($basicAuthCredentialsValid) {
        return AccessResult::allowed();
      }
      else {
        return AccessResult::forbidden();
      }
    }
    else {
      return AccessResult::allowed();
    }

  }

  /**
   * Checks if the HTTP basic authentication credentials are valid for request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return bool
   *   TRUE if the HTTP basic authentication credentials are valid.
   */
  private function checkIfBasicAuthCredentialsAreValid(Request $request) {
    if ($request->server->has('PHP_AUTH_USER') && $request->server->has('PHP_AUTH_PW')) {
      $username = $request->server->get('PHP_AUTH_USER');
      $password = $request->server->get('PHP_AUTH_PW');
    }
    elseif (!empty($request->server->get('HTTP_AUTHORIZATION'))) {
      list($username, $password) = explode(':', base64_decode(substr($request->server->get('HTTP_AUTHORIZATION'), 6)), 2);
    }
    elseif (!empty($request->server->get('REDIRECT_HTTP_AUTHORIZATION'))) {
      list($username, $password) = explode(':', base64_decode(substr($request->server->get('REDIRECT_HTTP_AUTHORIZATION'), 6)), 2);
    }

    if (isset($username, $password) && $username === $this->configManager->getUsername() && $password == $this->configManager->getPassword()) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
