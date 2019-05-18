<?php

namespace Drupal\configuration_ignore_test\Plugin\ConfigIgnore;

use Drupal\Core\Plugin\PluginBase;
use Drupal\config_ignore_keys\Plugin\ConfigurationIgnorePluginInterface;
use Drupal\Tests\config_ignore_keys\Kernel\ConfigIgnoreTest;

/**
 * Node config ignore plugin.
 *
 * @ConfigurationIgnorePlugin(
 *   id = "node_config_ignore",
 *   description = "The configuration for ignoring node configuration",
 * )
 */
class NodeConfigIgnore extends PluginBase implements ConfigurationIgnorePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getConfigurations() {
    $node_type_name = ConfigIgnoreTest::NODE_TYPE_NAME;
    return [
      "node.type.$node_type_name" => ['name'],
    ];
  }

}
