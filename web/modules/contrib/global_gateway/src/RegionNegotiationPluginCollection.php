<?php

namespace Drupal\global_gateway;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of tips.
 */
class RegionNegotiationPluginCollection extends DefaultLazyPluginCollection {

  /**
   * {@inheritdoc}
   */
  protected $pluginKey = 'plugin';

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\global_gateway\RegionNegotiationTypeInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * Provides uasort() callback to sort plugins.
   */
  public function sortHelper($aID, $bID) {
    $a = $this->get($aID);
    $b = $this->get($bID);
    return $a->getWeight() > $b->getWeight();
  }

}
