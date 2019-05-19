<?php

namespace Drupal\trail_graph\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Base class for Trail graph data plugins.
 */
abstract class TrailGraphDataBase extends PluginBase implements TrailGraphDataInterface, ContainerFactoryPluginInterface {

}
