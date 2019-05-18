<?php

namespace Drupal\node_access_rebuild_progressive\Commands;

use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 */
class NodeAccessRebuildProgressiveCommands extends DrushCommands {

  /**
   * Fully rebuild node access.
   *
   *
   * @command node-access-rebuild-progressive:rebuild
   * @aliases node-access-rebuild-progressive
   */
  public function accessRebuildProgressive() {
    _drush_node_access_rebuild_progressive_rebuild();
  }

}
