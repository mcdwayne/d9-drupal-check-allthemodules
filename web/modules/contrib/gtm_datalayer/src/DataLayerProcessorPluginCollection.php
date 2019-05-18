<?php

namespace Drupal\gtm_datalayer;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Provides a collection of GTM dataLayer processor plugins.
 */
class DataLayerProcessorPluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * The GTM dataLayer processor's ID this plugin collection belongs to.
   *
   * @var string
   */
  protected $dataLayerId;

  /**
   * Constructs a new DataLayerProcessorPluginCollection.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param string $instance_id
   *   The ID of the plugin instance.
   * @param string $datalayer_id
   *   The unique ID of the GTM dataLayer entity using this plugin.
   */
  public function __construct(PluginManagerInterface $manager, $instance_id, $datalayer_id) {
    parent::__construct($manager, $instance_id, []);

    $this->dataLayerId = $datalayer_id;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\gtm_datalayer\Plugin\DataLayerProcessorInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    try {
      if (!$instance_id) {
        throw new PluginException("The GTM dataLayer '{$this->dataLayerId}' did not specify a plugin.");
      }

      parent::initializePlugin($instance_id);
    }
    catch (PluginException $e) {
      throw $e;
    }
  }

}
