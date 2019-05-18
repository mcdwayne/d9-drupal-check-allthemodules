<?php

namespace Drupal\monster_menus\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * @MigrateSource(
 *   id = "d7_mm_tree_revision",
 *   source_module = "monster_menus"
 * )
 */
class D7MMTreeRevision extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $q = $this->select('mm_tree_revisions', 'r')
      ->fields('r', ['mmtid', 'vid', 'name', 'alias', 'parent', 'uid', 'default_mode', 'theme', 'hover', 'rss', 'mtime', 'muid', 'node_info', 'previews', 'hidden', 'comment']);
    $q->innerJoin('mm_tree', 't', 't.mmtid = r.mmtid');
    $q->where('t.vid <> r.vid');
    return $q;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'mmtid' => $this->t('MM Tree ID'),
      'vid' => $this->t('Revision ID'),
      'name' => $this->t('Name of tree entry'),
      'alias' => $this->t('URL alias'),
      'parent' => $this->t('MM Tree ID of parent'),
      'uid' => $this->t('User ID of owner'),
      'default_mode' => $this->t('Access mode(s) for anonymous user'),
      'theme' => $this->t('Theme for this page and its children'),
      'hover' => $this->t('Displayed when mouse hovers over menu entry'),
      'rss' => $this->t('RSS feed is enabled'),
      'mtime' => $this->t('Time of last modification'),
      'muid' => $this->t('ID of user who made last modification'),
      'node_info' => $this->t('Default attribution display mode'),
      'previews' => $this->t('Show only teasers'),
      'hidden' => $this->t('Page is hidden in menu'),
      'comment' => $this->t('Default comment display mode'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'vid' => [
        'type' => 'integer',
        'unsigned' => FALSE,
        // 'alias' is the alias for the table containing 'vid'
        'alias' => 'r',
      ],
    ];
  }

}
