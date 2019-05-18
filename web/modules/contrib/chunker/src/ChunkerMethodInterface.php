<?php

namespace Drupal\chunker;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Executable\ExecutableInterface;

/**
 * Provides an interface for a Chunker Method plugin.
 *
 * @see plugin_api
 */
interface ChunkerMethodInterface extends ExecutableInterface, PluginInspectionInterface {

}
