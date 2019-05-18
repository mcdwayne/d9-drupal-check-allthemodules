<?php

namespace Drupal\monster_menus\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityLink;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to a node's revision history.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_link_version_history")
 */
class NodeLinkVersionHistory extends EntityLink {

  /**
   * {@inheritdoc}
   */
  protected function getEntityLinkTemplate() {
    return 'version-history';
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink(ResultRow $row) {
    $this->options['alter']['query'] = $this->getDestinationArray();
    return parent::renderLink($row);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('revisions');
  }

}

//EntityLinkEdit {
//
//  /**
//   * {@inheritdoc}
//   */
//  protected function renderLink(ResultRow $row) {
//    // Ensure user has access to edit this node.
//    if (!$this->getEntity($row)->access('update')) {
//      return '';
//    }
//    $this->options['alter']['query'] = $this->getDestinationArray();
//    return parent::renderLink($row);
//  }
//
//}
