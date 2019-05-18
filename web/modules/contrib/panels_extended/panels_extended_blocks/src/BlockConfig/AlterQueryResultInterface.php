<?php

namespace Drupal\panels_extended_blocks\BlockConfig;

/**
 * Provides an interface to alter the result from the select query for a block.
 */
interface AlterQueryResultInterface {

  /**
   * Alters the results of the database select query.
   *
   * @param int[] $nids
   *   Reference to the current list of node IDs.
   */
  public function alterQueryResult(array &$nids);

}
