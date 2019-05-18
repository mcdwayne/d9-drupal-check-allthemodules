<?php

namespace Drupal\og_sm;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Interface for site manager classes.
 */
interface SiteManagerInterface {

  /**
   * Check if the given node is a Site type.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   *
   * @return bool
   *   Is a Site node.
   */
  public function isSite(NodeInterface $node);

  /**
   * Get the currently active Site.
   *
   * @return \Drupal\node\NodeInterface
   *   The active Site node (if any).
   */
  public function currentSite();

  /**
   * Load a Site node by its Node ID.
   *
   * This will only return a node object if:
   * - The node exists.
   * - The node is a Site node.
   *
   * @param int $id
   *   The Site Node ID.
   *
   * @return \Drupal\node\NodeInterface|false
   *   The Site Node (if any).
   */
  public function load($id);

  /**
   * Get the homepage url for a Site.
   *
   * @param \Drupal\node\NodeInterface $site
   *   The optional Site node. Will use the current Site from context if no Site
   *   is provided.
   *
   * @return \Drupal\Core\Url
   *   The Site homepage url object.
   */
  public function getSiteHomePage(NodeInterface $site = NULL);

  /**
   * Clear all cache for one site.
   *
   * This function does not clear the cache itself, it triggers the
   * \Drupal\og_sm\SiteEvents::CACHE_CLEAR event so modules can clear their
   * specific cached Site parts.
   *
   * @param \Drupal\node\NodeInterface $site
   *   The Site to clear the cache for.
   */
  public function clearSiteCache(NodeInterface $site);

  /**
   * Helper function to dispatch a site event for site changes.
   *
   * This is used to let other modules know that an action was performed on a
   * node that is a Site type. This reduces the code module maintainers need to
   * write to detect if something changed on a Site node type.
   *
   * @param string $action
   *   Action that is triggered.
   * @param \Drupal\node\NodeInterface $node
   *   The node for who to dispatch the event.
   */
  public function eventDispatch($action, NodeInterface $node);

  /**
   * Get an array of all Site Node ID's.
   *
   * WARNING: There is no node_access check on this function!
   *
   * @return int[]
   *   Array of all Site Node ID's.
   */
  public function getAllSiteNodeIds();

  /**
   * Get all the sites nodes within the platform.
   *
   * @return \Drupal\node\NodeInterface[]
   *   All Site nodes keyed by their nid.
   */
  public function getAllSites();

  /**
   * Helper function to get a list of Site objects from a list of group id's.
   *
   * @param \Drupal\Core\Entity\EntityInterface[][] $groups
   *   An associative array, keyed by group entity type, each item an array of
   *   group entities.
   *
   * @return \Drupal\node\NodeInterface[]
   *   All Site nodes keyed by their nid.
   */
  public function filterSitesFromGroups(array $groups);

  /**
   * Get all the Sites a node belongs to.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The site content.
   *
   * @return \Drupal\node\NodeInterface[]
   *   All Site nodes keyed by their nid.
   *
   * @deprecated Use ::getSitesFromEntity() instead.
   */
  public function getSitesFromContent(NodeInterface $node);

  /**
   * Get all the Sites a content entity belongs to.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The site content entity.
   *
   * @return \Drupal\node\NodeInterface[]
   *   All Site nodes keyed by their nid.
   */
  public function getSitesFromEntity(EntityInterface $entity);

  /**
   * Get the Site object the Site content node belongs to.
   *
   * If a content node belongs to multiple Sites, only the first will be
   * returned.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The site content.
   *
   * @return \Drupal\node\NodeInterface|false
   *   The site node (if any).
   *
   * @deprecated Use ::getSiteFromEntity() instead.
   */
  public function getSiteFromContent(NodeInterface $node);

  /**
   * Get the Site object the Site content entity belongs to.
   *
   * If the entity belongs to multiple Sites, only the first will be returned.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The site content entity.
   *
   * @return \Drupal\node\NodeInterface|false
   *   The site node (if any).
   */
  public function getSiteFromEntity(EntityInterface $entity);

  /**
   * Check if a given entity is content within a Site.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   Is Site content.
   */
  public function isSiteContent(EntityInterface $entity);

  /**
   * Check if a given entity belongs of the given Site.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   * @param \Drupal\node\NodeInterface $site
   *   The site node.
   *
   * @return bool
   *   Is Site member.
   */
  public function contentBelongsToSite(EntityInterface $entity, NodeInterface $site);

  /**
   * Gets content entities based on a site and entity type.
   *
   * @param \Drupal\node\NodeInterface $site
   *   The site node.
   * @param string $entity_type_id
   *   The entity type.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of content entities belonging to the passed site.
   */
  public function getEntitiesBySite(NodeInterface $site, $entity_type_id);

  /**
   * Get all the sites a User belongs to.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user object.
   *
   * @return \Drupal\node\NodeInterface[]
   *   All Site nodes keyed by their nid.
   */
  public function getUserSites(AccountInterface $account);

  /**
   * Get all the sites a User can manage.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user object.
   *
   * @return \Drupal\node\NodeInterface[]
   *   All Site nodes keyed by their nid.
   */
  public function getUserManageableSites(AccountInterface $account = NULL);

  /**
   * Check if a given account is member of the given Site.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user object.
   * @param \Drupal\node\NodeInterface $site
   *   The Site node object.
   *
   * @return bool
   *   Is site member.
   */
  public function userIsMemberOfSite(AccountInterface $account, NodeInterface $site);

  /**
   * Gets a user's membership to a site.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user object.
   * @param \Drupal\node\NodeInterface $site
   *   The Site node object.
   *
   * @return \Drupal\og\OgMembershipInterface
   *   Is membership entity.
   */
  public function getUserMembership(AccountInterface $account, NodeInterface $site);

}
