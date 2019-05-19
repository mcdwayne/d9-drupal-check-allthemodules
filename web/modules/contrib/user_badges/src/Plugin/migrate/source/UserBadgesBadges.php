<?php

namespace Drupal\user_badges\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal user badges source from database.
 *
 * @MigrateSource(
 *   id = "user_badges_badges"
 * )
 */
class UserBadgesBadges extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    // @TODO
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['bid']['type'] = 'integer';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('user_badges_badges', 'u')
      ->fields('u')
      ->orderBy('bid');
  }

}
