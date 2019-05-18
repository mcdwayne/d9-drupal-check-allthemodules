<?php

namespace Drupal\node_access_grants;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Collects all implementations of NodeAccessGrantsInterface.
 */
class NodeAccessGrantsCollection implements NodeAccessGrantsInterface {

  /**
   * @var array
   */
  private $implementations;

  /**
   * @param \Drupal\node_access_grants\NodeAccessGrantsInterface $implementation
   */
  public function addImplementation(NodeAccessGrantsInterface $implementation) {
    $this->implementations[] = $implementation;
  }

  /**
   * {@inheritdoc}
   */
  public function accessRecords(NodeInterface $node) {
    $records = [];
    /** @var NodeAccessGrantsInterface $implementation */
    foreach ($this->implementations as $implementation) {
      $records = array_merge($records, $implementation->accessRecords($node));
    }
    return $records;
  }

  /**
   * {@inheritdoc}
   */
  public function grants(AccountInterface $account, $op) {
    $grants = [];
    /** @var NodeAccessGrantsInterface $implementation */
    foreach ($this->implementations as $implementation) {
      $grants = array_merge($grants, $implementation->grants($account, $op));
    }
    return $grants;
  }
}