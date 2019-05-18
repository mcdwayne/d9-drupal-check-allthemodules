<?php

namespace Drupal\panels_extended_blocks\BlockConfig;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\node\NodeInterface;

/**
 * Basic block configuration for node lists.
 */
class NodeListBaseConfig implements AlterQueryInterface {

  /**
   * {@inheritdoc}
   */
  public function alterQuery(SelectInterface $query, $isCountQuery) {
    $query->condition('nfd.status', NodeInterface::PUBLISHED);

    $query->orderBy('nfd.created', 'DESC');
    $query->orderBy('nfd.nid', 'DESC');
  }

}
