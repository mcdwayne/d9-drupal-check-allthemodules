<?php

namespace Drupal\yaml_content;

use Drupal\Component\Plugin\ContextAwarePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * A generic interface to be implemented by all import and export processors.
 */
interface ContentProcessorInterface extends PluginInspectionInterface, ContextAwarePluginInterface {

}
