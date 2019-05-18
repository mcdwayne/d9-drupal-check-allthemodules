<?php

namespace Drupal\og_sm_content;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeListBuilder as NodeListBuilderBase;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\og_sm\SiteManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of node entities.
 *
 * This extends the original node list builder class but filters the rows in
 * site context.
 *
 * @see \Drupal\node\NodeListBuilder
 */
class NodeListBuilder extends NodeListBuilderBase {

  /**
   * The site manager.
   *
   * @var \Drupal\og_sm\SiteManagerInterface
   */
  protected $siteManager;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new NodeListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Drupal\og_sm\SiteManagerInterface $site_manager
   *   The site manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter, RedirectDestinationInterface $redirect_destination, SiteManagerInterface $site_manager, RouteMatchInterface $route_match, AccountInterface $current_user) {
    parent::__construct($entity_type, $storage, $date_formatter, $redirect_destination);
    $this->siteManager = $site_manager;
    $this->routeMatch = $route_match;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('redirect.destination'),
      $container->get('og_sm.site_manager'),
      $container->get('current_route_match'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort($this->entityType->getKey('id'));

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    // Filter the list on the current uid if this option is set in the route.
    if ($this->routeMatch->getRouteObject()->getOption('_filter_current_user')) {
      $query->condition('uid', $this->currentUser->id());
    }

    // If are viewing the node list from within a site context, filter it on
    // that site.
    $site = $this->siteManager->currentSite();
    if ($site) {
      $query->condition(OgGroupAudienceHelper::DEFAULT_FIELD, $site->id());
    }

    return $query->execute();
  }

}
