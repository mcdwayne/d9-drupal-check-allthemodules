<?php

namespace Drupal\workbench_reviewer\Plugin\views\argument;

use Drupal\user\Plugin\views\argument\Uid;

/**
 * Filters by given list of node title options.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsArgument("workbench_reviewer_node_reviewer")
 */
class NodeModerationReviewer extends Uid {

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    $this->query->addWhere(0, "$this->tableAlias.workbench_reviewer", $this->argument, 'IN');
  }

}
