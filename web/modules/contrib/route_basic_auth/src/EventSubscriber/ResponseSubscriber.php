<?php

namespace Drupal\route_basic_auth\EventSubscriber;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\route_basic_auth\Config\ConfigManager;
use Drupal\route_basic_auth\Routing\RouteHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscribes to kernel response events.
 *
 * @package Drupal\route_basic_auth\EventSubscriber\Response
 */
class ResponseSubscriber implements EventSubscriberInterface {

  /**
   * The config manager service.
   *
   * @var \Drupal\route_basic_auth\Config\ConfigManager
   */
  private $configManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * The route helper service.
   *
   * @var \Drupal\route_basic_auth\Routing\RouteHelper
   */
  private $routeHelper;

  /**
   * The flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  private $flood;

  /**
   * The current request.
   *
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  private $request;

  /**
   * ResponseSubscriber constructor.
   *
   * @param \Drupal\route_basic_auth\Config\ConfigManager $configManager
   *   The config manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\route_basic_auth\Routing\RouteHelper $routeHelper
   *   The route helper service.
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The current request stack.
   */
  public function __construct(ConfigManager $configManager, ConfigFactoryInterface $configFactory, RouteHelper $routeHelper, FloodInterface $flood, RequestStack $requestStack) {
    $this->configManager = $configManager;
    $this->configFactory = $configFactory;
    $this->routeHelper = $routeHelper;
    $this->flood = $flood;
    $this->request = $requestStack->getCurrentRequest();
  }

  /**
   * Use a low priority to ensure this is executed last.
   *
   * @return array
   *   The events to subscribe to.
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => ['onResponseEvent', -1000],
    ];
  }

  /**
   * Sets the status code to 401 and adds basic auth header.
   *
   * If the current route is configured as protected and the access is denied.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $responseEvent
   *   The response event.
   */
  public function onResponseEvent(FilterResponseEvent $responseEvent) {
    $request = $responseEvent->getRequest();
    $response = $responseEvent->getResponse();

    $routeName = $this->routeHelper->getRouteNameFromRequest($request);
    if ($routeName === NULL) {
      // Abort if the current request does not match a route.
      return;
    }

    $accessResult = $request->attributes->get('_access_result');

    if ($this->configManager->shouldRouteBeProtected($routeName)) {
      // Skip flood protection if it is not enabled.
      $isFloodProtectionEnabled = $this->configManager->isFloodProtectionEnabled();
      if ($isFloodProtectionEnabled) {
        $isFloodAllowed = $this->isFloodAllowed();
        // Always register a failed login event.
        $this->registerFloodAttempt();
      }
      else {
        $isFloodAllowed = TRUE;
      }

      if ($isFloodAllowed && $accessResult instanceof AccessResultForbidden) {
        /* Prompt for HTTP basic auth credentials if access is forbidden
        and flood limit is not reached. */
        $response->headers->add([
          'WWW-Authenticate' => 'Basic realm="Restricted Page"',
        ]);
        $response->setStatusCode(401);
      }
      elseif ($isFloodAllowed && $accessResult instanceof AccessResultAllowed) {
        if ($isFloodProtectionEnabled) {
          // Reset flood attempts on successful authentication.
          $floodIdentifier = $this->getFloodIdentifier();
          $this->flood->clear('route_basic_auth.failed_login_ip', $floodIdentifier);
        }

        return;
      }
      else {
        // Return access denied page if flood limit is reached.
        $response->setStatusCode(403);
      }
    }
  }

  /**
   * Flood protection: this is very similar to the user login form code.
   *
   * @see \Drupal\user\Form\UserLoginForm::validateAuthentication()
   * Do not allow any login from the current user's IP if the limit has been
   * reached. Default is 50 failed attempts allowed in one hour. This is
   * independent of the per-user limit to catch attempts from one IP to log
   * in to many different user accounts.  We have a reasonably high limit
   * since there may be only one apparent IP for all users at an institution.
   *
   * @return bool
   *   TRUE if the flood limit is not reached.
   *   FALSE if the flood limit is reached.
   */
  private function isFloodAllowed() {
    $isFloodAllowed = FALSE;
    $floodConfig = $this->getFloodConfig();

    $floodIdentifier = $this->getFloodIdentifier();

    /* Set flood threshold to 2 times the configured threshold,
    because the RepsonseSubscriber is called 2 times when the HTTP basic auth applies. */
    $floodThreshold = intval($floodConfig->get('ip_limit')) * 2;
    if ($this->flood->isAllowed('route_basic_auth.failed_login_ip', $floodThreshold, $floodConfig->get('ip_window'), $floodIdentifier)) {
      $isFloodAllowed = TRUE;
    }

    return $isFloodAllowed;
  }

  /**
   * Registers a failed IP based login attempt.
   */
  private function registerFloodAttempt() {
    $floodConfig = $this->getFloodConfig();
    $floodIdentifier = $this->getFloodIdentifier();
    $this->flood->register('route_basic_auth.failed_login_ip', $floodConfig->get('ip_window'), $floodIdentifier);
  }

  /**
   * Returns the identifier for the flood protection.
   *
   * @return string
   *   The client IP address.
   */
  private function getFloodIdentifier() {
    // Register flood events based on the IP only.
    return $this->request->getClientIP();
  }

  /**
   * Returns the flood configuration.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The flood configuration.
   */
  private function getFloodConfig() {
    return $this->configFactory->get('user.flood');
  }

}
