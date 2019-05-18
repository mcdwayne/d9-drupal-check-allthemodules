<?php

namespace Drupal\hn_frontend\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber subscribing to KernelEvents::REQUEST.
 */
class RedirectSubscriber implements EventSubscriberInterface {

  /**
   * The user who is requesting a page.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * The configFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * RedirectSubscriber constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory;
    $this->account = \Drupal::currentUser();
  }

  /**
   * Check if user has permissions to view the content else redirect.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Response event.
   */
  public function checkAuthStatus(GetResponseEvent $event) {
    $route_name = \Drupal::routeMatch()->getRouteName();

    // When user is authenticated, the user can access.
    if ($route_name === NULL || $this->account->isAuthenticated()) {
      return;
    }

    $excluded_routes = $this->config->get('hn_frontend.settings')->get('routes');

    // Check if route matches with excluded routes.
    if ($this->inList($excluded_routes, $route_name)) {
      // If it is, we ignore this route and exit this function.
      return;
    }

    // Redirect the user to the login page.
    $response = new RedirectResponse('/user/login', 301);
    $event->setResponse($response);
    $event->stopPropagation();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkAuthStatus'];
    $events[KernelEvents::EXCEPTION][] = ['checkAuthStatus'];
    return $events;
  }

  /**
   * Checks if $item is in $list.
   *
   * @param string[] $list
   *   A list of items that should return true. Can contain wildcards (*) and
   *   exclusions (~).
   * @param string $item
   *   The item to check against.
   *
   * @return bool
   *   TRUE of $item is in $list, FALSE if it isn't.
   */
  private function inList(array $list, $item) {
    // We reverse the list, so the latest in the list gets the most priority.
    $list = array_reverse($list);

    foreach ($list as $line) {
      // If the line starts with an ~, its an exclusion.
      $exclusion = substr($line, 0, 1) === '~';
      if ($exclusion) {
        $line = substr($line, 1);
      }

      // Change the line in something that can be thrown into a regex tester.
      $regexable = preg_quote($line, '/');

      // Change \* back into .*, so * can be used as a wildcard.
      $regexable = str_replace('\*', '.*', $regexable);
      if (preg_match('/^' . $regexable . '$/', $item)) {
        // We have a match!
        // Normally we would return true, but if it's an exclusion we return
        // need to return false.
        return !$exclusion;
      }
    }

    // If none of the lines match, it's not in the list.
    return FALSE;
  }

}
