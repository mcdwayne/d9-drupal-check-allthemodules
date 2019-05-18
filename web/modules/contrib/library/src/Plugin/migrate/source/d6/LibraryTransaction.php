<?php

namespace Drupal\library\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 library transaction source.
 *
 * @MigrateSource(
 *   id = "d6_library_transaction"
 * )
 */
class LibraryTransaction extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('library_transactions', 'lt')->fields('lt');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['tid']['type'] = 'integer';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'tid' => $this->t('Transaction id'),
      'item_id' => $this->t('Library item ID'),
      'nid' => $this->t('Node ID'),
      'uid' => $this->t('User id'),
      'action_aid' => $this->t('Action state the transaction takes'),
      'duedate' => $this->t('Due date'),
      'notes' => $this->t('Notes on item'),
      'created' => $this->t('Created'),
    ];
  }

}
