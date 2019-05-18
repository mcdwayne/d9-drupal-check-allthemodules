<?php

namespace Drupal\panels_extended_blocks\BlockConfig;

/**
 * Provides an interface to determine a list of node IDs which are fixed by the editorial staff.
 */
interface FixedNodesInterface {

  /**
   * The fixed nodes.
   *
   * The nodes are validated if they are published and within the number of items range.
   *
   * @return int[]
   *   A list of node IDs that have been fixed.
   */
  public function getFixedNodeIds();

}
