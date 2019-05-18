<?php

namespace Drupal\Tests\config_overlay\Functional;

use Drupal\Core\Config\StorageInterface;
use Drupal\FunctionalTests\Installer\InstallerExistingConfigTestBase;

/**
 * Tests installation with existing configuration with Configuration Overlay.
 *
 * @group config_overlay
 */
class ConfigOverlayTestingExistingConfigTest extends InstallerExistingConfigTestBase {

  use ConfigOverlayTestingTrait {
    getExpectedConfig as traitGetExpectedConfig;
    getOverriddenConfig as traitGetOverriddenConfig;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_overlay'];

  /**
   * A list of collections for this test's configuration.
   *
   * @var string[]
   */
  protected $collections = [StorageInterface::DEFAULT_COLLECTION];

  /**
   * {@inheritdoc}
   */
  protected function getConfigTarball() {
    // This tarball contains the following configuration files:
    // - core.extension.yml: With the extensions given by the Testing profile
    //   plus Config Overlay.
    // - system.date.yml: To set the default timezone to UTC
    // - system.file.yml: To set the temporary path to /tmp
    // - system.site.yml: To set the site UUID
    return __DIR__ . '/../../fixtures/config_install/testing_config_install.tar.gz';
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedConfig() {
    $expected_config = $this->traitGetExpectedConfig();
    unset(
      $expected_config[StorageInterface::DEFAULT_COLLECTION]['core.extension']['_core'],
      $expected_config[StorageInterface::DEFAULT_COLLECTION]['system.date']['_core'],
      $expected_config[StorageInterface::DEFAULT_COLLECTION]['system.file']['_core'],
      $expected_config[StorageInterface::DEFAULT_COLLECTION]['system.logging'],
      $expected_config[StorageInterface::DEFAULT_COLLECTION]['system.performance'],
      $expected_config[StorageInterface::DEFAULT_COLLECTION]['system.site']['_core']
    );
    return $expected_config;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOverriddenConfig() {
    $overridden_config = $this->traitGetOverriddenConfig();
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.date']['timezone']['default'] = 'UTC';
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.file']['path']['temporary'] = '/tmp';
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.site']['name'] = 'Site with Testing profile and Config Overlay';
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.site']['mail'] = 'admin@example.com';
    return $overridden_config;
  }

}
