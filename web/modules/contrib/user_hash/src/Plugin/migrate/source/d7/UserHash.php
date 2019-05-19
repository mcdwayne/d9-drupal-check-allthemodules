<?php

namespace Drupal\user_hash\Plugin\migrate\source\d7;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Extract user hashes from Drupal 7 database.
 *
 * @MigrateSource(
 *   id = "d7_user_hash"
 * )
 *
 * @author Richard Papp <richard.papp@boromino.com>
 */
class UserHash extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('users', 'u')
      ->fields('u', ['uid', 'hash']);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'uid' => $this->t('User ID'),
      'hash' => $this->t('User Hash'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'uid' => [
        'type' => 'integer',
        'alias' => 'u',
      ],
    ];
  }

}
