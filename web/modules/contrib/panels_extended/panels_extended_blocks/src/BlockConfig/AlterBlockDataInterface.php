<?php

namespace Drupal\panels_extended_blocks\BlockConfig;

/**
 * Provides an interface to alter the block data just before it's returned to the panel.
 */
interface AlterBlockDataInterface {

  /**
   * Alters the block data.
   *
   * @param int[] $nids
   *   Reference to the current list of node IDs.
   */
  public function alterBlockData(array &$nids);

}
