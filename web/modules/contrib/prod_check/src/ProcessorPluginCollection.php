<?php

namespace Drupal\prod_check;

use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Provides a container for lazily loading Processor plugins.
 */
class ProcessorPluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\prod_check\Plugin\ProdCheckProcessorInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

}
