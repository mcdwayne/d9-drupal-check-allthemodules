<?php

namespace Drupal\nodeownership;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides handler for nodeownership usage functions.
 */
class NodeownershipClaimUsage {

  protected $account;

  protected $entityManager;

  protected $entityQuery;

  /**
   * Construct nodeownership usage.
   *
   * @param EntityManagerInterface $entityManager
   *   Entity Manager.
   * @param AccountInterface $account
   *   User's Account.
   * @param QueryFactory $entityQuery
   *   Query Factory.
   */
  public function __construct(EntityManagerInterface $entityManager, AccountInterface $account, QueryFactory $entityQuery) {
    $this->entityManager = $entityManager;
    $this->account = $account;
    $this->entityQuery = $entityQuery;
  }

  /**
   * Provide all claims for node id.
   *
   * @param int $nid
   *   Nid of the node.
   *
   * @return array|null
   *   All claims nid(s) in array format.
   */
  public function getClaimByNid($nid) {
    $claimIds = $this->buildQuery(array('nid' => $nid));
    return $claimIds;
  }

  /**
   * Check claim for current user and node.
   *
   * @param int $nid
   *   Node ID.
   * @param int $uid
   *   User ID.
   *
   * @return array | NULL
   *   All the claim ids.
   */
  public function checkClaim($nid, $uid = NULL) {
    $conditions  = array(
      'nid' => $nid,
    );
    if ($uid != NULL) {
      $conditions['uid'] = $uid;
    }
    $claimIds = $this->buildQuery($conditions);
    return $claimIds;
  }

  /**
   * Build query for given conditions.
   *
   * @param array $conditions
   *   Condition in the format of array('key' => 'value').
   *
   * @return array | NULL
   *   Result for the above query.
   */
  private function buildQuery($conditions = array()) {
    // Use the factory to create a query object for node entities.
    $query = $this->entityQuery->get('nodeownership_claim');
    if (!empty($conditions)) {
      foreach ($conditions as $key => $value) {
        if (is_array($value)) {
          $query->condition($key, $value, 'IN');
        }
        else {
          $query->condition($key, $value);
        }
      }
    }
    $result = $query->execute();
    return $result;
  }

  /**
   * Load claim entity.
   */
  public function load($claimId) {
    $nodeownership_claim = $this->entityManager->getStorage('nodeownership_claim');
    $claim = $nodeownership_claim->load($claimId);
    return $claim;
  }

  /**
   * Check status for user's claimed node.
   *
   * @param int $nid
   *   Node ID of node.
   *
   * @return int
   *    Status.
   */
  public function claimedByMe($nid) {
    $status = NULL;
    $uid = $this->account->id();
    $claimId = $this->checkClaim($nid, $uid);
    if (!empty($claimId)) {
      $claim = $this->load(array_pop($claimId));
      $status = $claim->getStatus();
    }
    return $status;
  }

  /**
   * Claim status of current node.
   */
  public function claimedStatus($nid) {
    $status = NULL;
    $conditions = array(
      'nid' => $nid,
      'status' => NODEOWNERSHIP_CLAIM_APPROVED,
    );
    $claimedStatus = $this->buildQuery($conditions);
    if (!empty($claimedStatus)) {
      $status = NODEOWNERSHIP_CLAIM_APPROVED;
    }
    return $status;
  }

}
