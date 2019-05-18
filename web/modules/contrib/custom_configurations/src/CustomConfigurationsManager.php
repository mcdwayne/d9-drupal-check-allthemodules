<?php

namespace Drupal\custom_configurations;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactory;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxy;

/**
 * Helper for locale settings/variable-related methods.
 *
 * @package Drupal\custom_configurations
 */
class CustomConfigurationsManager {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Default key/value store service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactory
   */
  protected $keyValue;

  /**
   * The custom configurations plugin manager.
   *
   * @var \Drupal\custom_configurations\CustomConfigurationsPluginManager
   */
  protected $pluginManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The Database Cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The ModuleHandlerInterface.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactory $keyvalue
   *   Default key/value store service.
   * @param \Drupal\custom_configurations\CustomConfigurationsPluginManager $plugin_manager
   *   The custom configurations plugin manager.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Database cache.
   */
  public function __construct(ConfigFactory $config_factory, LanguageManagerInterface $language_manager, ModuleHandlerInterface $module_handler, KeyValueFactory $keyvalue, CustomConfigurationsPluginManager $plugin_manager, AccountProxy $current_user, CacheBackendInterface $cache) {
    $this->configFactory = $config_factory;
    $this->languageManager = $language_manager;
    $this->moduleHandler = $module_handler;
    $this->keyValue = $keyvalue;
    $this->pluginManager = $plugin_manager;
    $this->currentUser = $current_user;
    $this->cache = $cache;
  }

  /**
   * Get a current language variable from the file configs.
   *
   * @param string $plugin_id
   *   Which config to fetch a value from?
   * @param string $var_name
   *   The id of the value to fetch.
   *
   * @return mixed|array|null
   *   May return a value for a given $var_name,
   *   array of all values for the current $plugin_id
   *   or NULL if nothing is found.
   */
  public function getFileCurrentLanguageConfig($plugin_id, $var_name = NULL) {
    $language = $this->languageManager->getCurrentLanguage();
    return $this->getFileConfig($plugin_id, $var_name, $language);
  }

  /**
   * Get a current language variable from the database configs.
   *
   * @param string $plugin_id
   *   Which config to fetch a value from?
   * @param string $var_name
   *   The id of the value to fetch.
   *
   * @return mixed|array|null
   *   May return a value for a given $var_name,
   *   array of all values for the current $plugin_id
   *   or NULL if nothing is found.
   */
  public function getDbCurrentLanguageConfig($plugin_id, $var_name = NULL) {
    $language = $this->languageManager->getCurrentLanguage();
    return $this->getDbConfig($plugin_id, $var_name, $language);
  }

  /**
   * Get a localized variable from the file configs.
   *
   * @param string $plugin_id
   *   Which config to fetch a value from?
   * @param string $var_name
   *   The id of the value to fetch. If not set return all values.
   * @param string|object|null|false $language
   *   The language to get the config from. If not set return the global value.
   *
   * @return mixed|array|null
   *   May return a value for a given $var_name,
   *   array of all values for the current $plugin_id
   *   or NULL if nothing is found.
   */
  public function getFileConfig($plugin_id, $var_name = NULL, $language = NULL) {
    $variable = NULL;
    $original_language = $this->languageManager->getConfigOverrideLanguage();
    // No need to add language to the key, setConfigOverrideLanguage will handle it.
    $key = $this->getConfigKey($plugin_id);
    // Priority #1: Language-specific variable.
    if ($language && $this->languagesAvailable()) {
      $language = $this->getApplicableLanguageObject($language);
      $this->languageManager->setConfigOverrideLanguage($language);
      $config = $this->configFactory->get($key);
      if ($config) {
        $variable = $var_name === NULL ? $config->get() : $config->get($var_name);
      }
    }
    // Priority #2: Global variable.
    if (!$variable) {
      $this->languageManager->setConfigOverrideLanguage($original_language);
      $config = $this->configFactory->getEditable($key);
      if ($config) {
        $variable = $var_name === NULL ? $config->get() : $config->get($var_name);
      }
    }

    return $variable;
  }

  /**
   * Get a localized variable from the database configs.
   *
   * @param string $plugin_id
   *   Which config to fetch a value from?
   * @param string $var_name
   *   The id of the value to fetch. If not set return all values.
   * @param string|object|null|false $language
   *   The language to get the config from. If not set return the global value.
   *
   * @return mixed|array|null
   *   May return a value for a given $var_name,
   *   array of all values for the current $plugin_id
   *   or NULL if nothing is found.
   */
  public function getDbConfig($plugin_id, $var_name = NULL, $language = NULL) {
    $variable = NULL;
    // Priority #1: Language-specific variable.
    if ($language && $this->languagesAvailable()) {
      $language = $this->getApplicableLanguageObject($language);
      $key = $this->getConfigKey($plugin_id, $language->getId());
      $config = $this->keyValue->get($key);
      if ($config) {
        $variable = $var_name === NULL ? $config->getAll() : $config->get($var_name);
      }
    }
    // Priority #2: Global variable.
    if (!$variable) {
      $key = $this->getConfigKey($plugin_id);
      $config = $this->keyValue->get($key);
      if ($config) {
        $variable = $var_name === NULL ? $config->getAll() : $config->get($var_name);
      }
    }
    return $variable;
  }

  /**
   * Generates key for data storage.
   *
   * @param string $plugin_id
   *   Which config to fetch a value from?
   * @param string|object|null $language
   *   The language to get the config from.
   *
   * @return string
   *   Returns the key of the storage.
   */
  public function getConfigKey($plugin_id, $language = NULL) {
    $key = 'custom_configurations.';
    $key .= $language ? $plugin_id . '.' . $language : $plugin_id;
    return $key;
  }

  /**
   * Check if module language enabled.
   *
   * @return bool
   *   Returns true or false.
   */
  public function languagesAvailable() {
    return $this->moduleHandler->moduleExists('language');
  }

  /**
   * Takes a langcode or language object and returns an object.
   *
   * @param string|null|\Drupal\Core\Language\LanguageInterface $language
   *   The language to be tested.
   *
   * @return \Drupal\Core\Language\LanguageInterface
   *   Returns actual Language.
   */
  public function getApplicableLanguageObject($language) {
    if ($language instanceof Language) {
      return $language;
    }
    if ($language) {
      if (is_string($language)) {
        $language_object = $this->languageManager->getLanguage($language);
        if ($language_object) {
          return $language_object;
        }
      }
    }
    return $this->languageManager->getCurrentLanguage();
  }

  /**
   * Retrieves configuration plugins.
   *
   * @return array
   *   Returns and array of available plugins.
   */
  public function getConfigPlugins() {

    $plugins = &drupal_static(__FUNCTION__);

    if (!isset($plugins)) {

      $plugins = [];
      // Fetch the user's roles to check against plugin definition permissions.
      $acct_roles = $this->currentUser->getRoles();

      // Fetch the registered config plugins from all modules...
      $plugin_definitions = $this->pluginManager->getDefinitions();

      // Sort plugins by weight.
      uasort($plugin_definitions, function ($a, $b) {
        if ($a['weight'] == $b['weight']) {
          return 0;
        }
        return ($a['weight'] < $b['weight']) ? -1 : 1;
      });

      // ...and iterate through them.
      foreach ($plugin_definitions as $definition) {
        // Don't show modules hard-coded as "disabled".
        if (empty($definition['disabled'])) {
          // Only check roles IF plugin has allowed roles defined.
          if (!empty($definition['allowed_roles']) && $this->currentUser->id() != 1) {
            // Ensure the current user is permitted to see the config.
            if (!array_intersect($acct_roles, $definition['allowed_roles'])) {
              continue;
            }
          }
          // Generate category machine name if exists.
          if (!empty($definition['category'])) {
            $definition['category_id'] = Html::getId($definition['category']);
          }
          // Fetch the plugin's form elements.
          $plugins[$definition['id']] = $definition;
        }
      }

      // To avoid double route rebuild let's cancel it
      // if it was already set on rebuild by means of running
      // "Clear all caches" in user interface.
      if (empty($this->cache->get('custom_configurations_routes_rebuild')->data) && PHP_SAPI !== 'cli') {
        $this->cache->set('custom_configurations_routes_rebuild', TRUE);
      }

    }

    return $plugins;
  }

  /**
   * Retrieves all plugins categories.
   *
   * @return array
   *   Returns and array of available categories.
   */
  public function getConfigPluginCategories() {
    $categories = [];
    $plugins = $this->getConfigPlugins();

    foreach ($plugins as $plugin) {
      if (!empty($plugin['category_id'])) {
        $categories[$plugin['category_id']] = $plugin['category'];
      }
    }
    ksort($categories);
    return $categories;
  }

}
