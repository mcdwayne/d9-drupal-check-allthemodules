<?php

namespace Drupal\micro_sitemap\EventSubscriber;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\ResettableStackedRouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use League\Container\Exception\NotFoundException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Routing\RouteMatch;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class Subscriber.
 *
 * @package Drupal\micro_sitemap
 */
class MicroSitemapSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * The micro site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   the language manager.
   * @param \Drupal\Core\Routing\ResettableStackedRouteMatchInterface $current_route_match
   *   The current route match service.
   * @param \Drupal\micro_site\SiteNegotiatorInterface $negotiator
   *   The micro site negotiator.
   */
  public function __construct(AccountInterface $current_user, LanguageManagerInterface $language_manager, ResettableStackedRouteMatchInterface $current_route_match, SiteNegotiatorInterface $negotiator) {
    $this->currentUser = $current_user;
    $this->languageManager = $language_manager;
    $this->currentRouteMatch = $current_route_match;
    $this->negotiator = $negotiator;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST] = ['sitemapNotFound'];
    return $events;
  }

  /**
   * This method is called whenever the kernel.request event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function sitemapNotFound(GetResponseEvent $event) {
    $request = $event->getRequest();

    $route_name = $this->currentRouteMatch->getRouteName();
    if ($route_name !== 'sitemap.page') {
      return;
    }

    // If we've got an exception, nothing to do here.
    if ($request->get('exception') != NULL) {
      return;
    }

    $site = $this->negotiator->getActiveSite();
    if (!$site instanceof SiteInterface) {
      return;
    }

    $data = $site->getData('micro_sitemap');
    if (empty($data)) {
      throw new NotFoundHttpException();
    }

  }
}
