<?php

namespace Drupal\upgrade_tool;

use Drupal\Component\Utility\NestedArray;
use Drupal\config_import\ConfigParamUpdaterService;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Advanced version of ConfigParamUpdaterService.
 *
 * With checking of manual config change.
 */
class ConfigParamUpgradeTool extends ConfigParamUpdaterService {

  /**
   * Entity type manger.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger Entity Storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $upgradeLogStorage;

  /**
   * ConfigImporterService constructor.
   *
   * @param ConfigManagerInterface $config_manager
   *   ConfigManager.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    ConfigManagerInterface $config_manager,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->upgradeLogStorage = $this->entityTypeManager->getStorage('upgrade_log');
    parent::__construct($config_manager, $logger_factory);
  }

  /**
   * {@inheritdoc}
   */
  public function update($config, $config_name, $param) {
    // Get base storage config.
    if (!file_exists($config)) {
      $this->logger->error($this->t('File @file does not exist.', ['@file' => $config]));
      return;
    }
    if ($this->isManuallyChanged($config_name)) {
      // Skip config update and log this to logger entity.
      $this->updateLoggerEntity($config, $config_name, $param);
      $dashboard_url = Url::fromRoute('view.upgrade_dashboard.dashboard');
      $dashboard_link = Link::fromTextAndUrl(t('Upgrade dashboard'), $dashboard_url);
      $this->logger->error($this->t('Could not update config @name. Please add this changes manual. More info here - @link.',
        [
          '@name' => $config_name,
          '@link' => $dashboard_link->toString(),
        ]
      ));
      return;
    }
    $storage_config = Yaml::decode(file_get_contents($config));
    // Retrieve a value from a nested array with variable depth.
    $key_exists = FALSE;
    $update_value = NestedArray::getValue($storage_config, explode('.', $param), $key_exists);
    if (!$key_exists) {
      $this->logger->info(
        $this->t('Param "@param" does not exist in config @name.',
        ['@name' => $config_name, '@param' => $param])
      );
      return;
    }
    // Get active storage config.
    $config_factory = $this->configManager->getConfigFactory();
    $config = $config_factory->getEditable($config_name);
    if ($config->isNew() && empty($config->getOriginal())) {
      $this->logger->error($this->t('Config @name does not exist.', ['@name' => $config_name]));
      return;
    }
    // Update value retrieved from storage config.
    $config->set($param, $update_value);
    // Add upgrade_tool param for detecting this upgrade
    // in '@upgrade_tool.event_subscriber'.
    $config->set('upgrade_tool', TRUE);
    $config->save();
    $this->logger->info($this->t('Param "@param" in config @name was updated.',
      [
        '@name' => $config_name,
        '@param' => $param,
      ]
    ));
  }

  /**
   * Check if config exist in upgrade_log entity list.
   *
   * @param string $config_name
   *   Config name.
   *
   * @return bool
   *   TRUE if config was changed.
   */
  public function isManuallyChanged($config_name) {
    $configs = $this->upgradeLogStorage->loadByProperties([
      'name' => $config_name,
    ]);
    return empty($configs) ? FALSE : TRUE;
  }

  /**
   * Update Upgrade log entity.
   *
   * @param string $config
   *   Config full name with path.
   * @param string $config_name
   *   Config name.
   * @param string $param
   *   Identifier to store value in configuration.
   *
   * @return int|bool
   *   Entity ID in case of success.
   */
  private function updateLoggerEntity($config, $config_name, $param) {
    $entities = $this->upgradeLogStorage->loadByProperties([
      'name' => $config_name,
    ]);
    if (empty($entities)) {
      return FALSE;
    }
    $upgrade_log = array_shift($entities);
    $upgrade_log->setConfigPath($config)
      ->setConfigProperty($param)
      ->save();

    return $upgrade_log->id();
  }

}
