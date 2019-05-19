<?php

namespace Drupal\wordpress_db_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * WordPress user migration source plugin.
 *
 * @MigrateSource(
 *   id = "wp_user"
 * )
 */
class WpUser extends WpSqlBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'ID' => $this->t('User ID'),
      'user_login' => $this->t('User login ID.'),
      'user_pass' => $this->t('User password (hashed).'),
      'user_nicename' => $this->t('User slug.'),
      'user_email' => $this->t('User email.'),
      'user_url' => $this->t('User URL.'),
      'user_registered' => $this->t('Registration timestamp.'),
      'user_activation_key' => $this->t('Activation key.'),
      'user_status' => $this->t('User Status.'),
      'display_name' => $this->t('User display name.'),
      'user_meta' => $this->t('User meta details.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['ID']['type'] = 'integer';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('users', 'u')->fields('u');
    $query->orderBy('u.user_registered');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('user_meta', $this->getMetaValues(
      'usermeta',
      'user_id',
      $row->getSourceProperty('ID')));

    $row->setSourceProperty('user_registered', $this->strToTime($row->getSourceProperty('user_registered')));

    return parent::prepareRow($row);
  }

}
