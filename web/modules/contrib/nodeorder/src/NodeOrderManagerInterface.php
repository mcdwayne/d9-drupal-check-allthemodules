<?php

namespace Drupal\nodeorder;

use Drupal\node\NodeInterface;

/**
 * Provides an interface defining a NodeOrderManager.
 */
interface NodeOrderManagerInterface {

  /**
   * Push new or newly orderable node to the top of ordered list.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to add to the top of the list.
   * @param int $tid
   *   The term ID to order the node in.
   */
  public function addToList(NodeInterface $node, $tid);

  /**
   * Get the minimum and maximum weights available for ordering nodes on a term.
   *
   * @param int $tid
   *   The tid of the term from which to check values.
   * @param bool $reset
   *   (optional) Select from or reset the cache.
   *
   * @return array
   *   An associative array with elements 'min' and 'max'.
   */
  public function getTermMinMax($tid, $reset = FALSE);

  /**
   * Determines if a given vocabulary is orderable.
   *
   * @param string $vid
   *   The vocabulary vid.
   *
   * @return bool
   *   Returns TRUE if the given vocabulary is orderable.
   */
  public function vocabularyIsOrderable($vid);

  /**
   * Finds all nodes that match selected taxonomy conditions.
   *
   * NOTE: This is nearly a direct copy of taxonomy_select_nodes() -- see
   *       http://drupal.org/node/25801 if you find this sort of copy and
   *       paste upsetting...
   *
   *
   * @param array $tids
   *   An array of term IDs to match.
   * @param string $operator
   *   How to interpret multiple IDs in the array. Can be "or" or "and".
   * @param int $depth
   *   How many levels deep to traverse the taxonomy tree. Can be a nonnegative
   *   integer or "all".
   * @param bool $pager
   *   Whether the nodes are to be used with a pager (the case on most Drupal
   *   pages) or not (in an XML feed, for example).
   * @param string $order
   *   The order clause for the query that retrieve the nodes.
   * @param int $count
   *   If $pager is TRUE, the number of nodes per page, or -1 to use the
   *   backward-compatible 'default_nodes_main' variable setting.  If $pager
   *   is FALSE, the total number of nodes to select; or -1 to use the
   *   backward-compatible 'feed_default_items' variable setting; or 0 to
   *   select all nodes.
   *
   * @return \Drupal\Core\Database\StatementInterface
   *   A resource identifier pointing to the query results.
   */
  public function selectNodes($tids = [], $operator = 'or', $depth = 0, $pager = TRUE, $order = 'n.sticky DESC, n.created DESC', $count = -1);

  /**
   * Determine if a given node can be ordered in any vocabularies.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   *
   * @return bool
   *   Returns TRUE if the node has terms in any orderable vocabulary.
   */
  public function canBeOrdered(NodeInterface $node);

  /**
   * Get a list of term IDs on a node that can be ordered.
   *
   * This method uses the `taxonomy_index` table to determine which terms on a
   * node are orderable.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check for orderable term IDs.
   * @param bool $reset
   *   Flag to reset cached data.
   *
   * @return int[]
   *   Returns an array of the node's tids that are in orderable vocabularies.
   *
   * @see self::getOrderableTidsFromNode()
   */
  public function getOrderableTids(NodeInterface $node, $reset = FALSE);

  /**
   * Get all term IDs on a node that are on orderable vocabularies.
   *
   * Returns an array of the node's tids that are in orderable vocabularies.
   * Slower than self::getOrderableTids() but needed when tids have already been
   * removed from the database.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to find term IDs for.
   *
   * @return int[]
   *   An array of term IDs.
   */
  public function getOrderableTidsFromNode(NodeInterface $node);

  /**
   * Reorder list in which the node is dropped.
   *
   * When a node is removed, recalculates the ordering for a given term ID.
   *
   * @param int $tid
   *   The term ID.
   */
  public function handleListsDecrease($tid);

}
