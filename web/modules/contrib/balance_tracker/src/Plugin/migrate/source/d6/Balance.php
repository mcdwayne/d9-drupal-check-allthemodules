<?php

namespace Drupal\balance_tracker\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 user balance from database.
 *
 * @MigrateSource(
 *   id = "d6_user_balance"
 * )
 */
class Balance extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('balance_items', 'b')
      ->fields('b', array_keys($this->fields()))
      ->condition('b.bid', 0, '>');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'bid' => $this->t('Balance ID'),
      'uid' => $this->t('User ID'),
      'timestamp' => $this->t('Timestamp'),
      'type' => $this->t('Type'),
      'message' => $this->t('Message'),
      'amount' => $this->t('Amount'),
      'balance' => $this->t('Balance'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'bid' => [
        'type' => 'integer',
        'alias' => 'b',
      ],
    ];
  }

}
