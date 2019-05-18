<?php

namespace Drupal\config_ignore_keys\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\config_ignore_keys\Plugin\Manager\ConfigurationIgnorePluginManager;
use Drupal\language\Config\LanguageConfigFactoryOverride;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ConfigImportEventSubscriber.
 *
 * @package Drupal\config_ignore_keys\EventSubscriber
 */
class ConfigImportEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The config key ignore plugin manager.
   *
   * @var \Drupal\config_ignore_keys\Plugin\Manager\ConfigurationIgnorePluginManager
   */
  protected $pluginManager;

  /**
   * List of all plugins defined.
   *
   * @var array
   */
  protected $plugins;

  /**
   * The language override factory.
   *
   * @var \Drupal\language\Config\LanguageConfigFactoryOverride
   */
  protected $languageConfigFactoryOverride;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * ConfigImportEventSubscriber constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\config_ignore_keys\Plugin\Manager\ConfigurationIgnorePluginManager $pluginManager
   *   The plugin manager.
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   *   The language manager.
   * @param \Drupal\language\Config\LanguageConfigFactoryOverride $languageConfigFactoryOverride
   *   The language config override factory.
   */
  public function __construct(StateInterface $state,
                              ConfigFactoryInterface $configFactory,
                              ConfigurationIgnorePluginManager $pluginManager,
                              LanguageManager $languageManager,
                              LanguageConfigFactoryOverride $languageConfigFactoryOverride) {
    $this->state = $state;
    $this->configFactory = $configFactory;
    $this->pluginManager = $pluginManager;
    $this->languageManager = $languageManager;
    $this->languageConfigFactoryOverride = $languageConfigFactoryOverride;

    $this->plugins = $pluginManager->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::IMPORT_VALIDATE][] = ['onConfigValidate', 20];
    $events[ConfigEvents::IMPORT][] = ['onConfigImport', 20];
    return $events;
  }

  /**
   * Reacts to configuration validation.
   *
   * Fetches the  plugins containing the needed configurations to be ignored and
   * saves in state their initial value.
   */
  public function onConfigValidate() {
    foreach ($this->plugins as $plugin) {
      foreach ($this->pluginManager->createInstance($plugin['id'])->getConfigurations() as $configFile => $keys) {
        if (is_array($keys)) {
          foreach ($keys as $configKey) {
            $this->saveStateWithConfig($configFile, $configKey);
          }
        }
        else {
          $this->saveStateWithConfig($configFile, $configFile);
        }
      }
    }
  }

  /**
   * Saves the old configuration for the ignored config keys.
   */
  public function onConfigImport() {
    foreach ($this->plugins as $plugin) {
      foreach ($this->pluginManager->createInstance($plugin['id'])->getConfigurations() as $configFile => $keys) {
        if (is_array($keys)) {
          foreach ($keys as $configKey) {
            $this->saveConfigFromState($configFile, $configKey);
          }
        }
        else {
          $this->saveConfigFileFromState($configFile, $configFile);
        }
      }
    }
  }

  /**
   * Fetches the key data from the file and saves it into state.
   *
   * @param string $configFile
   *   The config file name.
   * @param string $configKey
   *   The configuration key which needs to be ignored.
   */
  private function saveStateWithConfig($configFile, $configKey) {
    $config = $this->configFactory->getEditable($configFile);
    foreach ($this->languageManager->getLanguages() as $language) {
      $config_translation = $this->languageConfigFactoryOverride->getOverride($language->getId(), $configFile);
      if (!$config_translation->isNew()) {
        $data = $config_translation->get($configKey);
        if (isset($data)) {
          $this->state->set($language->getId() . $configFile . $configKey, $data);
        }
      }
    }

    if (!empty($config) && NULL !== $config = $config->get($configKey)) {
      $this->state->set($configFile . $configKey, $config);
    }
  }

  /**
   * Fetches the state variable defined by key/file and saves it.
   *
   * @param string $configFile
   *   The config file name.
   * @param string $configKey
   *   The configuration key which needs to be ignored.
   */
  private function saveConfigFromState($configFile, $configKey) {
    foreach ($this->languageManager->getLanguages() as $language) {
      $stateValue = $stateValue = $this->state->get($language->getId() . $configFile . $configKey);
      if (isset($stateValue)) {
        $config_translation = $this->languageConfigFactoryOverride->getOverride($language->getId(), $configFile);
        $config_translation->set($configKey, $stateValue);
        $config_translation->save();
      }
    }
    $stateValue = $this->state->get($configFile . $configKey);
    if (isset($stateValue)) {
      $config = $this->configFactory->getEditable($configFile);
      if (!empty($config)) {
        $config->set($configKey, $stateValue);
        $config->save();
      }
    }
  }

  /**
   * Fetches the state variable defined by key/file and saves it.
   *
   * @param string $configFile
   *   The config file name.
   * @param string $configKey
   *   The configuration key which needs to be ignored.
   */
  private function saveConfigFileFromState($configFile, $configKey) {
    foreach ($this->languageManager->getLanguages() as $language) {
      $stateValue = $stateValue = $this->state->get($language->getId() . $configFile . $configKey);
      if (isset($stateValue)) {
        $config_translation = $this->languageConfigFactoryOverride->getOverride($language->getId(), $configFile);
        $config_translation->setData($stateValue);
        $config_translation->save();
      }
    }
    $stateValue = $this->state->get($configFile . $configKey);
    if (isset($stateValue)) {
      $config = $this->configFactory->getEditable($configFile);
      if (!empty($config)) {
        $config->setData($stateValue);
        $config->save();
      }
    }
  }

}
