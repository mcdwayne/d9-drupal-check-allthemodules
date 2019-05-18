<?php

namespace Drupal\joomla_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for beer user accounts.
 *
 * @MigrateSource(
 *   id = "joomla_user"
 * )
 */
class JoomlaUser extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('xi83f_users', 'ju')
                ->fields('ju', ['id', 'name', 'username', 'email', 'password']);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('Account ID'),
      'name' => $this->t('Account name (for display)'),
      'username' => $this->t('Account name (for login)'),
      'email' => $this->t('Account email'),
      'password' => $this->t('Account password (raw)'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
        'alias' => 'ju',
      ],
    ];
  }

}
