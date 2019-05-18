<?php

namespace Drupal\bundle_override_user\Plugin\bundle_override\EntityTypes;

use Drupal\bundle_override\Manager\Objects\AbstractBundleOverrideObjectsPluginManager;

/**
 * Plugin implementation of the 'BundleOverrideEntityTypes'.
 *
 * @BundleOverrideEntityTypes(
 *   id = "user"
 * )
 */
class UserBPluginManager extends AbstractBundleOverrideObjectsPluginManager {

  /**
   * The entity type id.
   */
  const ENTITY_TYPE_ID = 'user';

  /**
   * The service name.
   */
  const SERVICE_NAME = 'bundle_override.user_plugin_manager';

  /**
   * {@inheritdoc}
   */
  public function getDefaultEntityClass() {
    return '\Drupal\user_bundle\Entity\TypedUser';
  }

  /**
   * {@inheritdoc}
   */
  public function getRedefinerClass() {
    return 'Drupal\bundle_override_user\Plugin\bundle_override\EntityTypes\user\UserB';

  }

  /**
   * {@inheritdoc}
   */
  public function getStorageClass() {
    return 'Drupal\bundle_override_user\Plugin\bundle_override\EntityTypes\user\UserBStorage';
  }

  /**
   * {@inheritdoc}
   */
  public function getServiceId() {
    return static::SERVICE_NAME;
  }

}
