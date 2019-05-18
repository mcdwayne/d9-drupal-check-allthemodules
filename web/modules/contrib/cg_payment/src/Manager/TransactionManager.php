<?php

namespace Drupal\cg_payment\Manager;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class TransactionManager.
 *
 * @package Drupal\cg_payment\Manager
 */
class TransactionManager {

  /**
   * The EntityTypeManager object.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityTypeManager $entityTypeManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Gets all pending transactions that are pending for over 20 minutes.
   *
   * @return array
   *   Array of Transaction objects or null.
   */
  public function getExpiredTransactions() {

    $expiration_date_time = new DrupalDateTime('-20 minutes');

    $storage = $this->entityTypeManager->getStorage('transaction');
    $query = $storage->getQuery();
    $query->condition('status', 'pending');
    $query->condition('created', $expiration_date_time->getTimestamp(), '<=');

    if ($trids = $query->execute()) {
      return $storage->loadMultiple($trids);
    }

    return NULL;
  }

}
