<?php

namespace Drupal\custom_configurations\TwigExtension;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\custom_configurations\CustomConfigurationsManager;

class CustomConfigurationsGetConfig extends \Twig_Extension {

  /**
   * Custom configurations manager service.
   *
   * @var \Drupal\custom_configurations\CustomConfigurationsManager
   */
  protected $customConfigManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructor.
   *
   * @param \Drupal\custom_configurations\CustomConfigurationsManager $custom_configurations_manager
   *   Custom configurations manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager service.
   */
  public function __construct(CustomConfigurationsManager $custom_configurations_manager, LanguageManagerInterface $language_manager) {
    $this->customConfigManager = $custom_configurations_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return "custom_configurations.twig_extension";
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('custom_configurations_get_file_config', [$this, 'getFileConfig']),
      new \Twig_SimpleFunction('custom_configurations_get_db_config', [$this, 'getDbConfig']),
    ];
  }

  /**
   * Get a localized variable from the file configs.
   *
   * @param string $plugin_id
   *   Which config to fetch a value from?
   * @param string $var_name
   *   The id of the value to fetch.
   * @param bool $global
   *   If passed TRUE or no languages available the global value will be returned,
   *   otherwise corresponding to current language.
   *
   * @return string
   *   Return a value for a given $var_name.
   */
  public function getFileConfig($plugin_id, $var_name, $global = FALSE) {
    $global = $global || !$this->customConfigManager->languagesAvailable();
    $language = $global ? NULL : $this->languageManager->getCurrentLanguage();
    return $this->customConfigManager->getFileConfig($plugin_id, $var_name, $language);
  }

  /**
   * Get a localized variable from the database configs.
   *
   * @param string $plugin_id
   *   Which config to fetch a value from?
   * @param string $var_name
   *   The id of the value to fetch.
   * @param bool $global
   *   If passed TRUE or no languages available the global value will be returned,
   *   otherwise corresponding to current language.
   *
   * @return string
   *   Return a value for a given $var_name.
   */
  public function getDbConfig($plugin_id, $var_name, $global = FALSE) {
    $global = $global || !$this->customConfigManager->languagesAvailable();
    $language = $global ? NULL : $this->languageManager->getCurrentLanguage();
    return $this->customConfigManager->getDbConfig($plugin_id, $var_name, $language);
  }

}
