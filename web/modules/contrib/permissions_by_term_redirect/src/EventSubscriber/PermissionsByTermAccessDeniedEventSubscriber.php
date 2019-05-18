<?php

namespace Drupal\permissions_by_term_redirect\EventSubscriber;

use Drupal\Core\Url;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\permissions_by_term\Event\PermissionsByTermDeniedEvent;

/**
 * Handles the Permission Denied event fired by permissions_by_term.
 */
class PermissionsByTermAccessDeniedEventSubscriber implements EventSubscriberInterface {

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * Constructs a new PermissionsByTermAccessDeniedEventSubscriber object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $kill_switch
   *   The kill switch.
   */
  public function __construct(AccountProxyInterface $current_user, RouteMatchInterface $route_match, KillSwitch $kill_switch) {
    $this->currentUser = $current_user;
    $this->routeMatch = $route_match;
    $this->killSwitch = $kill_switch;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events['permissions_by_term.access.denied'] = ['onPermissionsByTermDeniedEvent'];

    return $events;
  }

  /**
   * Called whenever the PermissionsByTermDeniedEvent event is dispatched.
   *
   * @param \Drupal\permissions_by_term\Event\PermissionsByTermDeniedEvent $event
   *   The event we are subscribed to.
   */
  public function onPermissionsByTermDeniedEvent(PermissionsByTermDeniedEvent $event): void {
    $current_node = $this->routeMatch->getParameter('node');

    // Are trying to directly access the restricted node?
    if ($current_node !== NULL && $current_node->id() === $event->getNid()) {
      /*
       * Is the user a guest? Then redirect to login, otherwise just show
       * Access Denied.
       */
      if ($this->currentUser->isAnonymous()) {
        $this->killSwitch->trigger();
        user_cookie_save(['permissions_by_term_redirect.requested_nid' => $event->getNid()]);
        $redirect_response = new RedirectResponse(Url::fromRoute('user.login')
          ->toString(), RedirectResponse::HTTP_TEMPORARY_REDIRECT);
        $redirect_response->setCache([
          'max_age' => 0,
        ]);
        $redirect_response->send();
      }
    }
  }

}
