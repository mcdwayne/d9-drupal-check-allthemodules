<?php

namespace Drupal\micro_node\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\Routing\Route;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\micro_site\SiteNegotiatorInterface;

/**
 * @TODO To remove. Not used. See MicroNodeManager->access() and hook_node_access.
 * Check access on node when it is associated with a site entity.
 */
class NodeAccess implements AccessInterface{

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * Constructs a NodeAccess object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack object.
   * @param \Drupal\micro_site\SiteNegotiatorInterface $site_negotiator
   *   The site negotiator.
   */
  function __construct(EntityTypeManagerInterface $entity_type_manager, RequestStack $requestStack, SiteNegotiatorInterface $site_negotiator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $requestStack;
    $this->negotiator = $site_negotiator;
  }

  /**
   * Checks access to the entity operation on the given route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\node\NodeInterface $node
   *   The node on which check access.
   * @param \Drupal\micro_site\Entity\SiteInterface $site
   *   The site entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account, NodeInterface $node, SiteInterface $site = NULL) {
    $site = $node->get('site_id')->referencedEntities();
    $site = reset($site);

    if (empty($site)) {
      return AccessResult::neutral();
    }

    static $active_site;

    // The node is not attached to a site. Nothing to do.
//  $node_site = $node->get('site_id')->referencedEntities();
//  $node_site = reset($node_site);
//  if (empty($node_site)) {
//    return AccessResult::neutral();
//  }


    if (!isset($active_site)) {
      $active = $this->negotiator->getActiveSite();
      if (empty($active)) {
        $active = $this->negotiator->loadFromRequest();
      }
      $active_site = $active;
    }

    // Check to see that we have a valid active site.
    // Without one, we are on the main host site, and we let the main site manage
    // access to node attached to sites.
    if (empty($active_site) && $site) {
      return AccessResult::neutral();
    }


    if ($site instanceof SiteInterface && $active_site->id() != $site->id()) {
      return AccessResult::forbidden();
    }

    $allowed = FALSE;

    if ($active_site->id() == $site->id()) {
      if ($node->isPublished()) {
        $allowed = TRUE;
      }
      elseif ($account->hasPermission('view unpublished micro pages')) {
        $allowed = TRUE;
      }
      elseif ($account->hasPermission('view own unpublished micro pages') && $account->id() == $node->getOwnerId()) {
        $allowed = TRUE;
      }
    }

    if ($allowed) {
      return AccessResult::allowed()
        ->cachePerPermissions()
        ->cachePerUser()
        ->addCacheableDependency($node);
    }

    // Otherwise site attached to site are not accessible outside their site.
    return AccessResult::forbidden();
  }

}
