<?php

namespace Drupal\monster_menus\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * @MigrateSource(
 *   id = "d7_mm_role2group",
 *   source_module = "monster_menus"
 * )
 */
class D7Role2Group extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('mm_role2group', 'r')
      ->fields('r', ['rid', 'gid', 'negative']);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'rid' => $this->t('Role ID'),
      'gid' => $this->t('MM Tree ID of group'),
      'negative' => $this->t('TRUE if the role should be the inverse of the group'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'rid' => [
        'type' => 'integer',
        'unsigned' => TRUE,
        'alias' => 'r',
      ],
    ];
  }

}
