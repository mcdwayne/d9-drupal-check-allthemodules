<?php

namespace Drupal\events_logging;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Class StorageBackendPluginManager.
 */
interface StorageBackendPluginManagerInterface extends PluginManagerInterface, FallbackPluginManagerInterface {

}
