<?php

namespace Drupal\icons;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Provides a collection of icon library plugins.
 */
class IconLibraryPluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * The icon library ID this plugin collection belongs to.
   *
   * @var string
   */
  protected $iconLibraryId;

  /**
   * Constructs a new IconLibraryPluginCollection.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param string $instance_id
   *   The ID of the plugin instance.
   * @param array $configuration
   *   An array of configuration.
   * @param string $icon_library_id
   *   The unique ID of the icon library entity using this plugin.
   */
  public function __construct(PluginManagerInterface $manager, $instance_id, array $configuration, $icon_library_id) {
    parent::__construct($manager, $instance_id, $configuration);

    $this->iconLibraryId = $icon_library_id;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\icons\IconLibraryPluginInterface
   *   Icon library plugin to return.
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    if (!$instance_id) {
      throw new PluginException("The icon '{$this->iconLibraryId}' did not specify a plugin.");
    }

    try {
      parent::initializePlugin($instance_id);
    }
    catch (PluginException $e) {
      throw $e;
    }
  }

}
