<?php

/**
 * @file
 * Contains \Drupal\migrate_example\Plugin\migrate\source\DemoUniUser.
 */

namespace Drupal\pe_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Source plugin for user accounts.
 *
 * @MigrateSource(
 *   id = "demo_uni_user"
 * )
 */
class DemoUniUser extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('pe_migrate_user', 'pmu')
      ->fields('pmu', ['name', 'mail', 'pass', 'created', 'picture', 'roles']);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'name' => $this->t('Username'),
      'mail' => $this->t('Email address'),
      'pass' => $this->t('Account password (raw)'),
      'created' => $this->t('Created date'),
      'picture' => $this->t('User picture'),
      'roles' => $this->t('User roles, pipe-separated'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'name' => [
        'type' => 'string',
        'alias' => 'pmu',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if ($value = $row->getSourceProperty('roles')) {
      $row->setSourceProperty('roles', explode('|', $value));
    }

    /**
     * Make sure we have a created time.
     */
    if (empty($row->getSourceProperty('created'))) {
      $row->setSourceProperty('created', date('Y-m-d H:i:s', REQUEST_TIME));
    }

    return parent::prepareRow($row);
  }

}
