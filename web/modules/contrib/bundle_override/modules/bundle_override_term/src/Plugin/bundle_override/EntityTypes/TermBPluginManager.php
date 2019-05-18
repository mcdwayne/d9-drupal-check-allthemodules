<?php

namespace Drupal\bundle_override_term\Plugin\bundle_override\EntityTypes;

use Drupal\bundle_override\Manager\Objects\AbstractBundleOverrideObjectsPluginManager;

/**
 * Plugin implementation of the 'BundleOverrideEntityTypes'.
 *
 * @BundleOverrideEntityTypes(
 *   id = "taxonomy_term"
 * )
 */
class TermBPluginManager extends AbstractBundleOverrideObjectsPluginManager {

  /**
   * The entity type id.
   */
  const ENTITY_TYPE_ID = 'taxonomy_term';

  /**
   * The service name.
   */
  const SERVICE_NAME = 'bundle_override.term_plugin_manager';

  /**
   * {@inheritdoc}
   */
  public function getDefaultEntityClass() {
    return '\Drupal\taxonomy\Entity\Term';
  }

  /**
   * {@inheritdoc}
   */
  public function getRedefinerClass() {
    return 'Drupal\bundle_override_term\Plugin\bundle_override\EntityTypes\taxonomy_term\TermB';

  }

  /**
   * {@inheritdoc}
   */
  public function getStorageClass() {
    return 'Drupal\bundle_override_term\Plugin\bundle_override\EntityTypes\taxonomy_term\TermBStorage';
  }

  /**
   * {@inheritdoc}
   */
  public function getServiceId() {
    return static::SERVICE_NAME;
  }

}
