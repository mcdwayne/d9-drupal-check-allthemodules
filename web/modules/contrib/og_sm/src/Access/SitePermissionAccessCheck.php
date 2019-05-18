<?php

namespace Drupal\og_sm\Access;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\og\OgAccessInterface;
use Drupal\og_sm\SiteManagerInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to for node add pages.
 *
 * @ingroup node_access
 */
class SitePermissionAccessCheck implements AccessInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * The site manager service.
   *
   * @var \Drupal\og_sm\SiteManagerInterface
   */
  protected $siteManager;

  /**
   * Constructs a EntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   * @param \Drupal\og_sm\SiteManagerInterface $site_manager
   *   The site manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, OgAccessInterface $og_access, SiteManagerInterface $site_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->ogAccess = $og_access;
    $this->siteManager = $site_manager;
  }

  /**
   * Creates an allowed access result if the permissions are present, neutral otherwise.
   *
   * @param \Drupal\node\NodeInterface $site
   *   The site node.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which to check permissions.
   * @param array $permissions
   *   The permissions to check.
   * @param string $conjunction
   *   (optional) 'AND' if all permissions are required, 'OR' in case just one.
   *   Defaults to 'AND'
   *
   * @return \Drupal\Core\Access\AccessResult
   *   If the account has the permissions, isAllowed() will be TRUE, otherwise
   *   isNeutral() will be TRUE.
   */
  protected function allowedIfHasPermissions(NodeInterface $site, AccountInterface $account, array $permissions, $conjunction = 'AND') {
    $access = FALSE;

    if ($conjunction === 'AND' && !empty($permissions)) {
      $access = TRUE;
      foreach ($permissions as $permission) {
        if (!$this->ogAccess->userAccess($site, $permission, $account)->isAllowed()) {
          $access = FALSE;
          break;
        }
      }
    }
    else {
      foreach ($permissions as $permission) {
        if ($this->ogAccess->userAccess($site, $permission, $account)->isAllowed()) {
          $access = TRUE;
          break;
        }
      }
    }

    return AccessResult::allowedIf($access);
  }

  /**
   * Checks access to the node add page for the node type.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\node\NodeInterface $node
   *   THe site node.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(Route $route, AccountInterface $account, NodeInterface $node) {
    if ($this->siteManager->isSite($node)) {
      $permission = $route->getRequirement('_site_permission');

      // Allow to conjunct the permissions with OR ('+') or AND (',').
      $split = explode(',', $permission);
      if (count($split) > 1) {
        return $this->allowedIfHasPermissions($node, $account, $split, 'AND');
      }
      else {
        $split = explode('+', $permission);
        return $this->allowedIfHasPermissions($node, $account, $split, 'OR');
      }
    }
    return AccessResult::neutral();
  }

}
