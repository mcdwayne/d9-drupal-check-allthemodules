<?php

namespace Drupal\prepared_data\Processor;

use Drupal\Core\Plugin\PluginBase;

/**
 * A base class for data processor plugins.
 */
abstract class ProcessorBase extends PluginBase implements ProcessorInterface {

  use ProcessorTrait;

}
