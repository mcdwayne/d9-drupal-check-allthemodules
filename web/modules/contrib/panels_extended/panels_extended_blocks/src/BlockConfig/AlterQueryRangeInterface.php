<?php

namespace Drupal\panels_extended_blocks\BlockConfig;

/**
 * Provides an interface to alter the select query which is executed to fetch the block content.
 */
interface AlterQueryRangeInterface {

  /**
   * Allows altering the query range values.
   *
   * @param int $start
   *   Reference to the current offset / start of the query range.
   * @param int $length
   *   Reference to the current limit / length of the query range.
   */
  public function alterQueryRangeDelta(&$start, &$length);

}
