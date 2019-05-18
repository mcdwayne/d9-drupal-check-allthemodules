<?php

namespace Drupal\prod_check;

use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Provides a container for lazily loading prod check plugins.
 */
class CheckPluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\prod_check\Plugin\ProdCheckInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

}
