<?php

namespace Drupal\workflow_participants\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Access\NodeRevisionAccessCheck;
use Drupal\node\NodeInterface;
use Symfony\Component\Routing\Route;

/**
 * Access check for revisions and revision history.
 */
class RevisionCheck implements AccessInterface {

  /**
   * The node revision access checker service.
   *
   * @var \Drupal\node\Access\NodeRevisionAccessCheck
   */
  protected $nodeRevisionAccess;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * The participant storage.
   *
   * @var \Drupal\workflow_participants\WorkflowParticipantsStorageInterface
   */
  protected $participantStorage;

  /**
   * Constructs the access check.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\node\Access\NodeRevisionAccessCheck $node_revision_check
   *   The default node revision access checker.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, NodeRevisionAccessCheck $node_revision_check) {
    $this->nodeRevisionAccess = $node_revision_check;
    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->participantStorage = $entity_type_manager->getStorage('workflow_participants');
  }

  /**
   * Checks access to the node revision and revision history.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param int $node_revision
   *   (optional) The node revision ID. If not specified, but $node is, access
   *   is checked for that object's revision.
   * @param \Drupal\node\NodeInterface $node
   *   (optional) A node object. Used for checking access to a node's default
   *   revision when $node_revision is unspecified. Ignored when $node_revision
   *   is specified. If neither $node_revision nor $node are specified, then
   *   access is denied.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, $node_revision = NULL, NodeInterface $node = NULL) {
    if ($node_revision) {
      $node = $this->nodeStorage->loadRevision($node_revision);
    }
    $operation = $route->getRequirement('_workflow_participants_revision');
    $participants = $this->participantStorage->loadForModeratedEntity($node);

    // Since the default node access check has been removed, it is added here
    // for the access checker to verify.
    // @see \Drupal\node\Access\NodeRevisionAccessCheck::access()
    $route->setRequirement('_access_node_revision', $operation);

    return $this->nodeRevisionAccess->access($route, $account, $node_revision, $node)
      ->orIf(
        AccessResult::allowedIf(
          $node
          // There should be at least 2 revisions.
          && ($this->nodeStorage->countDefaultLanguageRevisions($node) > 1)
          && ($operation === 'view')
          && ($participants->isEditor($account) || $participants->isReviewer($account))
        ))->addCacheableDependency($participants);
  }

}
