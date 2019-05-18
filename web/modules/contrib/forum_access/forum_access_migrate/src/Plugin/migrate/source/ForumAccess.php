<?php

namespace Drupal\forum_access_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Provides table source plugin.
 *
 * @MigrateSource(
 *   id = "forum_access",
 *   source_module = "forum_access"
 * )
 */
class ForumAccess extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('forum_access', 'fa')
      ->fields('fa')
      ->orderBy('fa.tid');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['tid']['type'] = 'integer';
    $ids['rid']['type'] = 'integer';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'tid' => $this->t('The term ID.'),
      'rid' => $this->t('The role ID'),
      'grant_view' => $this->t('Grant view'),
      'grant_update' => $this->t('Grant update'),
      'grant_delete' => $this->t('Grant delete'),
      'grant_create' => $this->t('Grant create'),
      'priority' => $this->t('Priority'),
      'moderators' => $this->t('Moderators'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    parent::prepareRow($row);
    // Get moderators.
    $tid = $row->getSourceProperty('tid');
    $query = $this->select('acl_user', 'au');
    $query->innerJoin('acl', 'a', 'au.acl_id = a.acl_id');
    $moderators = $query->fields('au', ['uid'])
      ->condition('a.module', 'forum_access')
      ->condition('a.number', $tid)
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('moderators', $moderators);
  }

}
