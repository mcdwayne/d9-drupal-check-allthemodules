<?php

namespace Drupal\monster_menus\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;
use Drupal\monster_menus\Entity\MMTree;

/**
 * @MigrateSource(
 *   id = "d7_mm_tree",
 *   source_module = "monster_menus"
 * )
 */
class D7MMTree extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $q = $this->select('mm_tree', 't')
      ->fields('t', ['mmtid', 'vid', 'name', 'alias', 'parent', 'uid', 'default_mode', 'weight', 'theme', 'sort_idx', 'sort_idx_dirty', 'hover', 'rss', 'ctime', 'cuid', 'node_info', 'previews', 'hidden', 'comment'])
      ->fields('r', ['mtime', 'muid']);
    $q->leftJoin('mm_tree_revisions', 'r', 'r.vid = t.vid');
    // We sort this way to ensure parent entries are imported first.
    $q->orderBy('sort_idx', 'ASC');
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
      'weight' => $this->t('Menu order'),
      'theme' => $this->t('Theme for this page and its children'),
      'sort_idx' => $this->t('Sort index'),
      'sort_idx_dirty' => $this->t('Sort index is dirty'),
      'hover' => $this->t('Displayed when mouse hovers over menu entry'),
      'rss' => $this->t('RSS feed is enabled'),
      'ctime' => $this->t('Creation time'),
      'cuid' => $this->t('User ID of creator'),
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
      'mmtid' => [
        'type' => 'integer',
        'unsigned' => FALSE,
        // 'alias' is the alias for the table containing 'mmtid'
        'alias' => 't',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $tree = MMTree::create(['mmtid' => $row->getSourceProperty('mmtid')]);
    $tree->setDatabase($this->database);
    $row->setDestinationProperty('extendedSettings', ['value' => $tree->getExtendedSettings()]);
    $row->setSourceProperty('mtime', 0);
    return parent::prepareRow($row);
  }

}
