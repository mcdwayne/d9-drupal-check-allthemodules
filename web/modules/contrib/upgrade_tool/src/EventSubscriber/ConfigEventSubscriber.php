<?php

namespace Drupal\upgrade_tool\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\features\FeaturesManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class ConfigEventSubscriber.
 *
 * @package Drupal\openy_upgrade_tool
 */
class ConfigEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The FeaturesManager.
   *
   * @var \Drupal\features\FeaturesManagerInterface
   */
  protected $featuresManager;

  /**
   * Entity type manger.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Upgrade log Entity Storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $upgradeLogEntityStorage;

  /**
   * Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * ConfigEventSubscriber constructor.
   *
   * @param \Drupal\features\FeaturesManagerInterface $features_manager
   *   Features Manager.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param ConfigFactoryInterface $config_factory
   *   Config Factory.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $loggerChannel
   *   Logger channel.
   */
  public function __construct(
    FeaturesManagerInterface $features_manager,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
    LoggerChannelInterface $loggerChannel) {

    $this->logger = $loggerChannel;
    $this->configFactory = $config_factory;
    $this->featuresManager = $features_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->upgradeLogEntityStorage = $this->entityTypeManager->getStorage('upgrade_log');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = ['onSavingConfig', 800];
    return $events;
  }

  /**
   * Get Upgrade tool tracked configs list.
   *
   * @param string $config_name
   *   Config name.
   *
   * @return bool
   *   TRUE if config is tracked.
   */
  public function configIsTracked($config_name) {
    $config = $this->configFactory->getEditable('upgrade_tool.settings');
    switch ($config->get('mode')) {
      case 'disabled':
        return FALSE;

      case 'all':
        return TRUE;

      case 'features':
        // Get upgrade_tool features list.
        $all_features = $config->get('features');
        if ($all_features) {
          // Leave only the selected features.
          $tracked_features = array_filter($all_features, function ($v) {
            return $v !== 0;
          });
          $features_configs = $this->featuresManager->listExistingConfig(TRUE);
          // Get tracked configs from features configs list.
          $tracked_configs = array_filter($features_configs, function ($module) use ($tracked_features) {
            return isset($tracked_features[$module]);
          });
          // Check if isset current config in tracked configs list.
          return isset($tracked_configs[$config_name]);
        }
        return FALSE;
    }

    return FALSE;
  }

  /**
   * Creates Upgrade Log entity.
   *
   * @param string $name
   *   Config name.
   *
   * @return int|bool
   *   Entity ID in case of success.
   */
  private function setUpgradeLog($name) {
    try {
      // Load Upgrade Log with this config name.
      $entities = $this->upgradeLogEntityStorage->loadByProperties(['name' => $name]);
      if (empty($entities)) {
        // Create new Upgrade Log entity for this config name if not exist.
        $upgrade_log_entity = $this->upgradeLogEntityStorage->create();
      }
      else {
        $upgrade_log_entity = array_shift($entities);
      }
      $upgrade_log_entity->setName($name);
      $upgrade_log_entity->save();
      return $upgrade_log_entity->id();
    }
    catch (\Exception $e) {
      $msg = 'Failed to save upgrade_log entity. Message: %msg';
      $this->logger->error($msg, ['%msg' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Subscriber Callback for the event.
   *
   * @param ConfigCrudEvent $event
   *   Configuration save event.
   */
  public function onSavingConfig(ConfigCrudEvent $event) {
    $config = $event->getConfig();
    $original = $config->getOriginal();
    if (empty($original)) {
      // Skip new config.
      return;
    }
    if ($original == $config->get()) {
      // Skip config without updates.
      return;
    }
    $config_name = $config->getName();
    if (!$this->configIsTracked($config_name)) {
      // Skip configs not related to tracked configs.
      return;
    }
    if (!$config->get('upgrade_tool')) {
      // This config was updated without upgrade_tool workflow.
      $this->setUpgradeLog($config_name);
      $this->logger->warning($this->t('You have manual updated @name config from upgrade tool tracked configs.', ['@name' => $config_name]));
    }
    else {
      // Remove upgrade_tool param from config.
      $config->clear('upgrade_tool');
      $this->logger->info($this->t('Upgrade tool was upgraded @name config.', ['@name' => $config_name]));
    }

  }

}
