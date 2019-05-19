<?php

namespace Drupal\yac_referral\EventSubscriber;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class CrmReferralRedirectAnonymous.
 *
 * @group yac_referral
 */
class CrmReferralRedirectAnonymous extends RouteSubscriberBase implements EventSubscriberInterface {

  /**
   * Variable that will store the AccountProxyInterface class.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Variable that will store the CurrentRouteMatch class.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Constructs a new CrmReferralRedirectAnonymous object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The account proxy interface of the current user.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match.
   */
  public function __construct(AccountProxyInterface $current_user, CurrentRouteMatch $current_route_match) {
    $this->currentUser = $current_user;
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST] = ['onRequest', 0];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // TODO: Implement alterRoutes() method.
  }

  /**
   * This method is called whenever the kernel.request event is dispatched.
   *
   * Redirects anonymous users to site registration page.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to which we are responding.
   */
  public function onRequest(GetResponseEvent $event) {
    $route_name = $this->currentRouteMatch->getRouteName();
    if ($route_name !== 'yac_referral.registration') {
      return;
    }
    else {
      $route_param = $this->currentRouteMatch->getParameter('affiliate_code');
    }
    if ($this->currentUser->isAnonymous()) {
      $url = Url::fromUri('internal:/user/register/' . $route_param);
      $event->setResponse(new RedirectResponse($url->toString()));
    }
    else {
      return;
    }
  }

}
