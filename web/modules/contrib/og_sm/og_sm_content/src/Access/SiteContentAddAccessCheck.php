<?php

namespace Drupal\og_sm_content\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\og\OgAccessInterface;
use Drupal\og_sm\SiteTypeManagerInterface;

/**
 * Determines access to for node add pages with a site context.
 */
class SiteContentAddAccessCheck implements AccessInterface {

  /**
   * The site type manager.
   *
   * @var \Drupal\og_sm\SiteTypeManagerInterface
   */
  protected $siteTypeManager;

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface
   */
  protected $ogAccess;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a SiteContentAddAccessCheck object.
   *
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG access service.
   * @param \Drupal\og_sm\SiteTypeManagerInterface $site_type_manager
   *   The site type manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(OgAccessInterface $og_access, SiteTypeManagerInterface $site_type_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->ogAccess = $og_access;
    $this->siteTypeManager = $site_type_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access to the node add page for the node type.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\node\NodeInterface $node
   *   The site node.
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   (optional) The node type. If not specified, access is allowed if there
   *   exists at least one node type for which the user may create a node.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, NodeInterface $node, NodeTypeInterface $node_type = NULL) {
    if ($node_type) {
      $access = $this->ogAccess->userAccess($node, "create {$node_type->id()} content", $account);
      if ($access->isAllowed()) {
        return AccessResult::allowed();
      }
      return AccessResult::neutral();
    }

    // Check if the user has create access to at least 1 content type.
    foreach ($this->siteTypeManager->getContentTypes() as $type) {
      if ($this->access($account, $node, $type)->isAllowed()) {
        return AccessResult::allowed();
      }
    }

    // No opinion.
    return AccessResult::neutral();
  }

}
