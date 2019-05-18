<?php

namespace Drupal\entity_ui\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\entity_ui\Entity\EntityTabInterface;

/**
 * Plugin collection for an entity tab's content plugin.
 *
 * This handles passing the entity tab entity to the plugin instance.
 */
class EntityTabLazyPluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * The entity tab entity to pass to the plugin when initializing it.
   */
  protected $entityTab;

  /**
   * Constructs a new DefaultSingleLazyPluginCollection object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param string $instance_id
   *   The ID of the plugin instance.
   * @param array $configuration
   *   An array of configuration.
   * @param EntityTabInterface $entity_tab
   *   The entity tab entity.
   */
  public function __construct(PluginManagerInterface $manager, $instance_id, array $configuration, EntityTabInterface $entity_tab) {
    $this->entityTab = $entity_tab;

    parent::__construct($manager, $instance_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    $this->set($instance_id, $this->manager->createInstance($instance_id, $this->configuration, $this->entityTab));
  }

}
