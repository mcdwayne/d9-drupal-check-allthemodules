<?php

namespace Drupal\micro_bibcite\EventSubscriber;

use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @TODO to delete.
 * Class CanonicalPageSubscriber.
 */
class BibCiteCanonicalPageSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\micro_site\SiteNegotiatorInterface definition
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * An array of canonical page for which prevent access.
   *
   * @var array
   */
  protected $canonicalPages;


  /**
   * BibCiteCanonicalPageSubscriber constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\micro_site\SiteNegotiatorInterface $negotiator
   */
  public function __construct(CurrentRouteMatch $current_route_match, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, SiteNegotiatorInterface $negotiator) {
    $this->currentRouteMatch = $current_route_match;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->negotiator = $negotiator;
    $this->canonicalPages = $this->getBibciteCanonicalPages();
  }

  /**
   * Get the canonical page's route names.
   *
   * @return array
   */
  protected function getBibciteCanonicalPages() {
    $pages = [
      'entity.bibcite_reference.canonical',
      'entity.bibcite_contributor.canonical',
      'entity.bibcite_keyword.canonical',
    ];
    return $pages;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST] = ['onRequest404'];

    return $events;
  }

  /**
   * This method is called whenever the kernel.request event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function onRequest404(GetResponseEvent $event) {
    $request = $event->getRequest();

    if ($this->currentUser->hasPermission('access bibcite canonical page')) {
      return;
    }

    $active_site = $this->negotiator->getActiveSite();
    if ($active_site instanceof SiteInterface &&
      $this->currentUser->hasPermission('view micro bibcite reference') &&
      $this->currentRouteMatch->getRouteName() == 'entity.bibcite_reference.canonical') {
      return;
    }

    // If we've got an exception, nothing to do here.
    if ($request->get('exception') != NULL) {
      return;
    }

    $route_name = $this->currentRouteMatch->getRouteName();
    if (in_array($route_name, $this->canonicalPages)) {
      throw new NotFoundHttpException();
    }

  }

}
