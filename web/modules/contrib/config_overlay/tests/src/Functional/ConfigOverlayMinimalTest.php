<?php

namespace Drupal\Tests\config_overlay\Functional;

use Drupal\Core\Config\StorageInterface;
use Drupal\user\RoleInterface;

/**
 * Tests installation of the Minimal profile with Configuration Overlay.
 *
 * @group config_overlay
 */
class ConfigOverlayMinimalTest extends ConfigOverlayTestingTest {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected function getOverriddenConfig() {
    $overridden_config = parent::getOverriddenConfig();

    // Add any modules that are installed by Minimal, but not by Testing.
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['core.extension']['module'] += [
      'block' => 0,
      'dblog' => 0,
      'field' => 0,
      'filter' => 0,
      'node' => 0,
      'text' => 0,
    ];
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['core.extension']['module'] = module_config_sort($overridden_config[StorageInterface::DEFAULT_COLLECTION]['core.extension']['module']);

    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['core.extension']['theme'] = ['stark' => 0];

    /* @see node_install() */
    $role_ids = [RoleInterface::ANONYMOUS_ID, RoleInterface::AUTHENTICATED_ID];
    foreach ($role_ids as $role_id) {
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]["user.role.$role_id"] = [
        'permissions' => ['access content'],
      ];
    }

    return $overridden_config;
  }

}
