<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal_d5\Plugin\migrate\source\d5\User.
 */

namespace Drupal\migrate_drupal_d5\Plugin\migrate\source\d5;

use Drupal\user\Plugin\migrate\source\d6\User as UserBase;

/**
 * Drupal 5 user source from database.
 *
 * @MigrateSource(
 *   id = "d5_user"
 * )
 */
class User extends UserBase {

  /**
   * {@inheritdoc}
   */
  protected function baseFields() {
    $fields = parent::baseFields();
    // The only difference between D5 and D6 is only D6 has signature_format.
    unset($fields['signature_format']);
    return $fields;
  }

}
