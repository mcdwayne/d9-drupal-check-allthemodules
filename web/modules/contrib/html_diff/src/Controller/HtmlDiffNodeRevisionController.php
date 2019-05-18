<?php

namespace Drupal\html_diff\Controller;

use Drupal\node\NodeInterface;

/**
 * Returns responses for Node Revision routes.
 * This is a implementation of Drupal\diff\Controller\NodeRevisionController.
 */
class HtmlDiffNodeRevisionController extends HtmlDiffGenericRevisionController {

  /**
   * Returns a form for revision overview page.
   *
   * @param NodeInterface $node
   *   The node whose revisions are inspected.
   *
   * @return array
   *   Render array containing the revisions table for $node.
   */
  public function revisionOverview(NodeInterface $node) {
    return $this->formBuilder()->getForm('Drupal\diff\Form\RevisionOverviewForm', $node);
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
   *   If filter == 'raw-plain' markdown function is applied to the text before comparison.
   *   If filter == 'html-diff' the HTML is rendered for a better visual comparison.
   *
   * @return array
   *   Table showing the diff between the two node revisions.
   */
  public function compareNodeRevisions(NodeInterface $node, $left_revision, $right_revision, $filter) {
    $storage = $this->entityTypeManager()->getStorage('node');
    $route_match = \Drupal::routeMatch();
    $left_revision = $storage->loadRevision($left_revision);
    $right_revision = $storage->loadRevision($right_revision);
    $build = $this->compareEntityRevisions($route_match, $left_revision, $right_revision, $filter);
    return $build;
  }
}
