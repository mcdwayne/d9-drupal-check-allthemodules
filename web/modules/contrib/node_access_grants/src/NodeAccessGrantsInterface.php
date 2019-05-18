<?php

namespace Drupal\node_access_grants;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Central interface for implementing the Drupal access grants.
 */
interface NodeAccessGrantsInterface {

  /**
   * Returns the grants to be written for a given node.
   *
   * @param \Drupal\node\NodeInterface $node
   *
   * @return array
   */
  public function accessRecords(NodeInterface $node);

  /**
   * Inform the node access system what permissions the user has.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param $op
   *
   * @return array
   */
  public function grants(AccountInterface $account, $op);
}