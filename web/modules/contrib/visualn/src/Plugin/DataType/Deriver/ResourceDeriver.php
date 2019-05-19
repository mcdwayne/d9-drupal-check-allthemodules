<?php

/**
 * @file
 * Containts ResourceDeriver class. Based on FieldItemDeriver logic and structure
 */

namespace Drupal\visualn\Plugin\DataType\Deriver;

use Drupal\visualn\Manager\ResourceManager;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides data type plugins for each existing resource type plugin.
 */
class ResourceDeriver implements ContainerDeriverInterface {

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = [];

  /**
   * The base plugin ID this derivative is for.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The resource type plugin manager.
   *
   * @var \Drupal\visualn\Manager\ResourceManager
   */
  protected $visualNResourceManager;

  /**
   * Constructs a ResourceDeriver object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\visualn\Manager\ResourceManager $visualn_resource_manager
   *   The resource type plugin manager.
   */
  public function __construct($base_plugin_id, ResourceManager $visualn_resource_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->visualNResourceManager = $visualn_resource_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('plugin.manager.visualn.resource')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    if (!isset($this->derivatives)) {
      $this->getDerivativeDefinitions($base_plugin_definition);
    }
    if (isset($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->visualNResourceManager->getDefinitions() as $plugin_id => $definition) {
      $definition['definition_class'] = '\Drupal\visualn\Plugin\DataType\Deriver\ResourceDataDefinition';
      // @todo: check this option
      //$definition['list_definition_class'] = '\Drupal\Core\Field\BaseFieldDefinition';
      // @todo: check this option
      $definition['unwrap_for_canonical_representation'] = FALSE;
      $this->derivatives[$plugin_id] = $definition;
    }
    return $this->derivatives;
  }

}

