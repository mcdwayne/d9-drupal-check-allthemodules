<?php

namespace Drupal\micro_node;

use Drupal\micro_site\Entity\SiteInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\micro_site\SiteUsers;
use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\micro_node\MicroNodeFields;

/**
 * Handles the negotiation of the active domain record.
 */
interface MicroNodeManagerInterface {

  /**
   * Determines the current site id.
   *
   * @param array
   *   The current site Id or NULL if not site context found.
   */
  public static function getCurrentSiteId();

  /**
   * Get the sites owned by a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account proxy.
   * @return array
   *   An array of user's sites id.
   */
  public function getSitesByOwner(AccountInterface $account);

  /**
   * Get the sites referencing a user per role.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account proxy.
   * @param string $field_name
   *   The name of the field that holds our data.
   *
   * @return array
   *   An array of user's sites id.
   */
  public function getSitesReferencingUserPerRole(AccountInterface $account, $field_name = SiteUsers::MICRO_SITE_ADMINISTRATOR);

  /**
   * Get the sites referencing a user for any role.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account proxy.
   *
   * @return array
   *   An array of user's sites id.
   */
  public function getSitesReferencingUsers(AccountInterface $account);

  /**
   * Get the sites referencing a user with admin permissions (admin or manager).
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account proxy.
   *
   * @return array
   *   An array of user's sites id.
   */
  public function getSitesReferencingAdminUsers(AccountInterface $account);

  /**
   * Count the sites owned by a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account proxy.
   * @return integer|NULL
   *   The count of sites owned by the user.
   */
  public function getCountSitesByOwner(AccountInterface $account);

  /**
   * Get the main site from a node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node to check.
   *
   * @return \Drupal\micro_site\Entity\SiteInterface|NULL
   *   The site entity or NULL.
   */
  public function getMainSiteFromEntity(EntityInterface $node);

  /**
   * Get the secondary site access field values from an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to retrieve field data from.
   * @param string $field_name
   *   The name of the field that holds our data.
   *
   * @return array
   *   The site entities keyed by site id.
   */
  public function getSecondarySitesFromEntity(EntityInterface $entity, $field_name = MicroNodeFields::NODE_SITES);

  /**
   * Is the entity is published on all sites.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to retrieve field data from.
   *
   * @return boolean
   *   TRUE, if the entity is published on all sites. Otherwise FALSE.
   */
  public function isPublishedOnAllSites(EntityInterface $entity);

  /**
   * Is the entity is published on others sites.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to retrieve field data from.
   * @param string $field_name
   *   The name of the field that holds our data.
   *
   * @return boolean
   *   TRUE, if the entity is published on others sites. Otherwise FALSE.
   */
  public function onMultipleSites(EntityInterface $entity, $field_name = MicroNodeFields::NODE_SITES);

  /**
   * Does the entity can have several canonical url for each site ?
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to retrieve field data from.
   * @param string $field_name
   *   The name of the field that holds our data.
   *
   * @return boolean
   *   TRUE, if the entity can have several canonical url for each site.
   *   Otherwise FALSE.
   */
  public function hasMultipleCanonicalUrl(EntityInterface $entity, $field_name = MicroNodeFields::NODE_SITES_DISABLE_CANONICAL_URL);

  /**
   * Get the master host public base url.
   *
   * @return string
   */
  public function getMasterHostBaseUrl();

  /**
   * Get the secondary site access field values from an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to retrieve field data from.
   * @param string $field_name
   *   The name of the field that holds our data.
   *
   * @return array
   *   The site entities keyed by site id.
   */
  public function getAllSitesFromEntity(EntityInterface $entity, $field_name = MicroNodeFields::NODE_SITES);

  /**
   * Get the sites a user can reference.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account proxy.
   *
   * @return array
   *   An array of user's sites id.
   */
  public function getSitesUserCanReference(AccountInterface $account);

  /**
   * Get the sites a user can update administrative fields.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account proxy.
   *
   * @return array
   *   An array of user's sites id.
   */
  public function getSitesUserCanUpdateAdministrativeFields(AccountInterface $account);

  /**
   * Get the sites a user can update any content.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account proxy.
   *
   * @return array
   *   An array of user's sites id.
   */
  public function getSitesUserCanUpdateAnyContent(AccountInterface $account);

  /**
   * An user can cross publish content from a given site, to another sites.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account proxy.
   * @param \Drupal\micro_site\Entity\SiteInterface $site
   *   The site entity.
   *
   * @return boolean
   *   TRUE if the user can cross publish content from the given site.
   */
  public function userCanCrossPublish(AccountInterface $account, SiteInterface $site = NULL);

  /**
   * An user can create content on a given site.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account proxy.
   * @param \Drupal\micro_site\Entity\SiteInterface $site
   *   The site entity.
   *
   * @return boolean
   *   TRUE if the user can create content on the given site.
   */
  public function userCanCreateContent(AccountInterface $account, SiteInterface $site = NULL);

  /**
   * An user can access to the content tab overview.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account proxy.
   * @param \Drupal\micro_site\Entity\SiteInterface $site
   *   The site entity.
   *
   * @return boolean
   *   TRUE if the user can access to the content tab overview.
   */
  public function userCanAccessContentOverview(AccountInterface $account, SiteInterface $site = NULL);

  /**
   * Check access on node when associated with a site entity. Useful to control
   * if a node can be viewed if site entity is not published.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check.
   * @param string $op
   *   The operation to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account proxy.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  function nodeAccess(NodeInterface $node, $op, AccountInterface $account);

}
