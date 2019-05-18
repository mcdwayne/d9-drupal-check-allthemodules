<?php

namespace Drupal\migrate_plugins\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Provides a 'CasUser' migrate source.
 *
 * @MigrateSource(
 *  id = "d7_cas_user"
 * )
 */
class CasUser extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('cas_user', 'c')
      ->fields('c');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'aid' => $this->t('Cas user mapping ID.'),
      'uid' => $this->t('Drupal user ID.'),
      'cas_name' => $this->t('Unique authentication name.'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'aid' => [
        'type' => 'integer',
      ],
    ];
  }

}
