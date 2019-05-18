<?php

namespace Drupal\bundle_override_node\Plugin\bundle_override\EntityTypes;

use Drupal\bundle_override\Manager\Objects\AbstractBundleOverrideObjectsPluginManager;

/**
 * Plugin implementation of the 'BundleOverrideEntityTypes'.
 *
 * @BundleOverrideEntityTypes(
 *   id = "node"
 * )
 */
class NodeBPluginManager extends AbstractBundleOverrideObjectsPluginManager {

  /**
   * The entity type id.
   */
  const ENTITY_TYPE_ID = 'node';

  /**
   * The service name.
   */
  const SERVICE_NAME = 'bundle_override.node_plugin_manager';

  /**
   * {@inheritdoc}
   */
  public function getDefaultEntityClass() {
    return '\Drupal\node\Entity\Node';
  }

  /**
   * {@inheritdoc}
   */
  public function getRedefinerClass() {
    return 'Drupal\bundle_override_node\Plugin\bundle_override\EntityTypes\node\NodeB';

  }

  /**
   * {@inheritdoc}
   */
  public function getStorageClass() {
    return 'Drupal\bundle_override_node\Plugin\bundle_override\EntityTypes\node\NodeBStorage';
  }

  /**
   * {@inheritdoc}
   */
  public function getServiceId() {
    return static::SERVICE_NAME;
  }

}
