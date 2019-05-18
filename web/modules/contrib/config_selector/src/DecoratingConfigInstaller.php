<?php

namespace Drupal\config_selector;

use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Decorates the config.installer service so install_install_profiles() works.
 *
 * The install step that installs installation profiles calls the config
 * installer directly which results in the Configuration Selector not being able
 * to select configuration.
 */
class DecoratingConfigInstaller implements ConfigInstallerInterface {

  /**
   * The config.installer service.
   *
   * @var \Drupal\Core\Config\ConfigInstallerInterface
   */
  protected $decoratedService;

  /**
   * DecoratingConfigInstaller constructor.
   *
   * @param \Drupal\Core\Config\ConfigInstallerInterface $config_installer
   *   The config.installer service to decorate.
   * @param \Drupal\config_selector\ConfigSelector $config_selector
   *   The config_selector service.
   */
  public function __construct(ConfigInstallerInterface $config_installer, ConfigSelector $config_selector) {
    $this->decoratedService = $config_installer;
    $this->configSelector = $config_selector;
  }

  /**
   * {@inheritdoc}
   */
  public function installDefaultConfig($type, $name) {
    $this->decoratedService->installDefaultConfig($type, $name);
  }

  /**
   * {@inheritdoc}
   */
  public function installOptionalConfig(StorageInterface $storage = NULL, $dependency = []) {
    $this->decoratedService->installOptionalConfig($storage, $dependency);
    if ($storage === NULL && empty($dependency)) {
      $this->configSelector->selectConfig();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function installCollectionDefaultConfig($collection) {
    $this->decoratedService->installCollectionDefaultConfig($collection);
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceStorage(StorageInterface $storage) {
    $this->decoratedService->setSourceStorage($storage);
    return $this;
  }

  /**
   * Gets the configuration storage that provides the default configuration.
   *
   * @return \Drupal\Core\Config\StorageInterface|null
   *   The configuration storage that provides the default configuration.
   *   Returns null if the source storage has not been set.
   */
  public function getSourceStorage() {
    return $this->decoratedService->getSourceStorage();
  }

  /**
   * {@inheritdoc}
   */
  public function setSyncing($status) {
    $this->decoratedService->setSyncing($status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isSyncing() {
    return $this->decoratedService->isSyncing();
  }

  /**
   * {@inheritdoc}
   */
  public function checkConfigurationToInstall($type, $name) {
    $this->decoratedService->checkConfigurationToInstall($type, $name);
  }

}
