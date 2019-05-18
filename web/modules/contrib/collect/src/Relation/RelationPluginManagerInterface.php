<?php
/**
 * @file
 * Contains \Drupal\collect\Relation\RelationPluginManagerInterface.
 */

namespace Drupal\collect\Relation;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;

interface RelationPluginManagerInterface extends PluginManagerInterface, FallbackPluginManagerInterface {

}
