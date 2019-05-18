<?php

namespace Drupal\global_gateway\SwitcherData;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of the global gateway block line items.
 */
class SwitcherDataPluginCollection extends DefaultLazyPluginCollection {

  /**
   * {@inheritdoc}
   */
  protected $definitions;

  /**
   * {@inheritdoc}
   */
  public function &get($instance_id) {
    // @todo: do not construct if plugin doesn't exist.
    return parent::get($instance_id);
  }

}
