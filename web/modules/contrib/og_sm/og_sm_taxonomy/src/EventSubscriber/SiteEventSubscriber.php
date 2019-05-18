<?php

namespace Drupal\og_sm_taxonomy\EventSubscriber;

use Drupal\og_sm\Event\SiteEvent;
use Drupal\og_sm\Event\SiteEvents;
use Drupal\og_sm\SiteManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to the site events.
 */
class SiteEventSubscriber implements EventSubscriberInterface {

  /**
   * The site manager.
   *
   * @var \Drupal\og_sm\SiteManagerInterface
   */
  protected $siteManager;

  /**
   * Constructs a SiteEventSubscriber object.
   *
   * @param \Drupal\og_sm\SiteManagerInterface $site_manager
   *   The site manager.
   */
  public function __construct(SiteManagerInterface $site_manager) {

    $this->siteManager = $site_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SiteEvents::DELETE][] = 'onSiteDelete';
    return $events;
  }

  /**
   * Event listener triggered when a site is deleted.
   *
   * @param \Drupal\og_sm\Event\SiteEvent $event
   *   The site event.
   */
  public function onSiteDelete(SiteEvent $event) {
    $terms = $this->siteManager->getEntitiesBySite($event->getSite(), 'taxonomy_term');
    foreach ($terms as $term) {
      $term->delete();
    }
  }

}
