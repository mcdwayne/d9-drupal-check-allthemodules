<?php

namespace Drupal\micro_site\EventSubscriber;

use Drupal\Core\Session\AccountInterface;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;

/**
 * Class TermSubscriber.
 *
 * @package Drupal\micro_site
 */
class TermSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\micro_site\SiteNegotiatorInterface $site_negotiator
   *   The site negotiator.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The current route match.
   */
  public function __construct(AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, SiteNegotiatorInterface $site_negotiator, CurrentRouteMatch $route_match) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->negotiator = $site_negotiator;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST] = array('onRequest');
    return $events;
  }

  /**
   * This method prevent to access to node without a site_id field.
   *
   * @param GetResponseEvent $event
   *   The event object.
   */
  public function onRequest(GetResponseEvent $event) {
    $request = $event->getRequest();

    // If we've got an exception, nothing to do here.
    if ($request->get('exception') != NULL) {
      return;
    }

    // We are not on an site entity.
    $active_site = $this->negotiator->getActiveSite();
    if (!$active_site instanceof SiteInterface) {
      return;
    }

    /** @var \Drupal\taxonomy\Entity\Term $term */
    $term = $this->routeMatch->getParameter('taxonomy_term');
    $route_name = $this->routeMatch->getRouteName();
    if ($term instanceof TermInterface && $route_name == 'entity.taxonomy_term.canonical') {
      if (!$term->hasField('site_id') || !$term->hasField('site_all')) {
        throw new NotFoundHttpException();
      }
    }

  }

}
