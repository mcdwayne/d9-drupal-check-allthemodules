<?php

namespace Drupal\monster_menus\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for MM Tree entities.
 */
class MMTreeViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['mm_tree']['mm_tree_parents'] = array(
      'title' => t('MM page and children'),
      'help' => t('The MM page and all of its children'),
      'argument' => array(
        'id' => 'mm_tree_children',
      ),
    );
    $data['mm_tree']['node_field_data'] = [
      'title' => $this->t('Content on page(s)'),
      'help' => $this->t('Content that appears on the page(s)'),
      'relationship' => [
        'label' => t('Content on page(s)'),
        'base' => 'node_field_data',
        'base field' => 'nid',
        'id' => 'sequential_join',
        'relationship_table' => 'mm_tree',
        'joins' => [
          [
            'left table' => 'mm_tree',
            'left field' => 'mmtid',
            'table' => 'mm_node2tree',
            'field' => 'mmtid',
          ],
          [
            'left field' => 'nid',
            'table' => 'mm_node2tree',
            'field' => 'nid',
          ],
        ],
      ],
    ];

    $data['mm_tree']['mm_recycle'] = array(
      'title' => t('Recycled'),
      'help' => t('Whether or not the page is in a recycle bin'),
      'filter' => array(
        'id' => 'is_recycled',
        'real table' => 'mm_recycle',
        'real field' => 'id',
      ),
    );

    return $data;
  }

}
