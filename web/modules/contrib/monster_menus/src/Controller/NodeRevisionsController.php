<?php

/**
 * @file
 * Contains \Drupal\monster_menus\Controller\DefaultController.
 */

namespace Drupal\monster_menus\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Session\AccountInterface;
use Drupal\diff\Controller\NodeRevisionController;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for node revisions operations.
 */
class NodeRevisionsController extends ControllerBase {

  static public function menuAccessNodeRevisions(NodeInterface $node, AccountInterface $account) {
    if (!$node->access('update', $account) || !$node->access('view', $account) || (!$account->hasPermission('view revisions') && !$account->hasPermission('bypass node access'))) {
      return AccessResult::forbidden();
    }
    $num_versions = Database::getConnection('default')
      ->select('node_revision', 'r')
      ->fields('r', ['vid'])
      ->condition('r.nid', $node->id())
      ->countQuery()->execute()->fetchField();
    return AccessResult::allowedIf($num_versions > 1);
  }

  /**
   * Display an overview table of node revisions.
   *
   * @param NodeInterface $node
   *   The node to display revisions for
   * @return array
   *   The render array
   */
  public function revisionOverview(NodeInterface $node) {
    \Drupal::request()->query->remove('destination');
    if ($this->moduleHandler()->moduleExists('diff')) {
      /** @noinspection PhpMethodParametersCountMismatchInspection */
      return $this->formBuilder()->getForm('Drupal\diff\Form\RevisionOverviewForm', $node);
    }
    $callable = \Drupal::service('controller_resolver')
      ->getControllerFromDefinition('\Drupal\node\Controller\NodeController::revisionOverview');
    return $callable($node);
  }

  /**
   * Display a single node revision.
   *
   * @param NodeInterface $node
   *   The node to display revisions for
   * @param int $node_revision
   *   ID of the revision to display
   * @return array
   *   The render array
   */
  public function revisionShow(NodeInterface $node, $node_revision) {
    return mm_node_show($this->getNodeRevision($node, $node_revision));
  }

  /**
   * Display a node revision revert confirmation form.
   *
   * @param NodeInterface $node
   *   The node to revert a revision for
   * @param int $node_revision
   *   ID of the revision to revert to
   * @return array
   *   The render array
   */
  public function revisionRevertConfirm(NodeInterface $node, $node_revision) {
    $this->getNodeRevision($node, $node_revision);
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    return $this->formBuilder()->getForm('\Drupal\node\Form\NodeRevisionRevertForm', $node_revision);
  }

  /**
   * Display a node revision deletion confirmation form.
   *
   * @param NodeInterface $node
   *   The node to delete a revision for
   * @param int $node_revision
   *   ID of the revision to delete
   * @return array
   *   The render array
   */
  public function revisionDeleteConfirm(NodeInterface $node, $node_revision) {
    $this->getNodeRevision($node, $node_revision);
    /** @noinspection PhpMethodParametersCountMismatchInspection */
    return $this->formBuilder()->getForm('\Drupal\node\Form\NodeRevisionDeleteForm', $node_revision);
  }

  /**
   * Returns a table which shows the differences between two node revisions.
   *
   * @param NodeInterface $node
   *   The node whose revisions are compared.
   * @param $left_revision
   *   Vid of the node revision from the left.
   * @param $right_revision
   *   Vid of the node revision from the right.
   * @param $filter
   *   If $filter == 'raw' raw text is compared (including html tags)
   *   If filter == 'raw-plain' markdown function is applied to the text before
   *   comparison.
   *
   * @return array
   *   Table showing the diff between the two node revisions.
   */
  public function compareRevisions(NodeInterface $node, $left_revision, $right_revision, $filter) {
    if ($this->moduleHandler()->moduleExists('diff')) {
      $this->getNodeRevision($node, $left_revision);
      $this->getNodeRevision($node, $right_revision);
      $controller = NodeRevisionController::create(\Drupal::getContainer());
      return $controller->compareNodeRevisions($node, $left_revision, $right_revision, $filter);
    }

    throw new NotFoundHttpException();
  }

  /**
   * Load a specific node revision.
   *
   * @param NodeInterface $node
   * @param $revision_id
   * @return NodeInterface|null
   */
  private function getNodeRevision(NodeInterface $node, $revision_id) {
    /** @var $revision NodeInterface */
    if (!$revision_id || !($revision = static::entityTypeManager()->getStorage('node')->loadRevision($revision_id)) || $revision->id() != $node->id()) {
      throw new NotFoundHttpException();
    }
    return $revision;
  }

}
