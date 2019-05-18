<?php

namespace Drupal\monster_menus\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * @MigrateSource(
 *   id = "d7_mm_tree_bookmarks",
 *   source_module = "monster_menus"
 * )
 */
class D7TreeBookmarks extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('mm_tree_bookmarks', 'b')
      ->fields('b', ['uid', 'type', 'weight', 'data']);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'uid' => $this->t('User ID'),
      'type' => $this->t('Type of data'),
      'weight' => $this->t('Bookmark weight'),
      'data' => $this->t('Misc. data'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'uid' => [
        'type' => 'integer',
        'unsigned' => TRUE,
        'alias' => 'b',
      ],
      'type' => [
        'type' => 'string',
        'alias' => 'b',
      ],
      'weight' => [
        'type' => 'integer',
        'alias' => 'b',
      ],
    ];
  }

}
