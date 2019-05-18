<?php

namespace Drupal\plugins_alter_connection_test\Plugin\migrate\source;

use Drupal\node\Plugin\migrate\source\d6\Node;

/**
 * Test source plugin that extends node.
 *
 * @MigrateSource(
 *   id = "connection_test",
 *   source_module = "plugins_alter_connection_test"
 * )
 */
class PluginsAlterConnection extends Node {}
