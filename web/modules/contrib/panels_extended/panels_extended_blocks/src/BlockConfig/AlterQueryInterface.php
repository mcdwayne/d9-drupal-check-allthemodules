<?php

namespace Drupal\panels_extended_blocks\BlockConfig;

use Drupal\Core\Database\Query\SelectInterface;

/**
 * Provides an interface to alter the select query which is executed to fetch the block content.
 *
 * NOTE: Do not apply range conditions here, use AlterQueryRangeInterface for that.
 */
interface AlterQueryInterface {

  /**
   * Allows altering the select query.
   *
   * NOTE: For query range conditions, use AlterQueryRangeInterface.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The current query.
   * @param bool $isCountQuery
   *   Indicates if we're doing the count query.
   */
  public function alterQuery(SelectInterface $query, $isCountQuery);

}
