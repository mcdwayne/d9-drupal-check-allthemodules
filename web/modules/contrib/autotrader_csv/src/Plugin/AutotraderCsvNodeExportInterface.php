<?php

namespace Drupal\autotrader_csv\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\node\NodeInterface;

/**
 * Defines an interface for Autotrader CSV Node Export plugins.
 */
interface AutotraderCsvNodeExportInterface extends ContainerFactoryPluginInterface, PluginInspectionInterface {

  /**
   * Tell the object which node it's working on.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node that this exporter is working on.
   */
  public function setNode(NodeInterface $node);

  /**
   * Generate the array that represents this node.
   *
   * @return array
   *   Return an array.
   */
  public function toArray();

  /**
   * Generate the string that represents this node.
   *
   * @return string
   *   Return a string.
   */
  public function toString();

}
