<?php

namespace Drupal\custom_configurations\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\KeyValueStore\KeyValueFactory;
use Drupal\Core\Session\AccountProxy;
use Drupal\custom_configurations\CustomConfigurationsManager;
use Drupal\custom_configurations\CustomConfigurationsPluginManager;
use Drupal\Core\Config\ConfigManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CustomConfigurationsForm.
 *
 * @package Drupal\custom_configurations\Form
 */
class CustomConfigurationsForm extends ConfigFormBase {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The custom configurations plugin manager.
   *
   * @var \Drupal\custom_configurations\CustomConfigurationsPluginManager
   */
  protected $customConfigurationsPluginManager;

  /**
   * The Drupal default config manager.
   *
   * @var \Drupal\Core\Config\ConfigManager
   */
  protected $configManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The custom configurations helper service.
   *
   * @var \Drupal\custom_configurations\CustomConfigurationsManager
   */
  protected $customConfigurationsManager;

  /**
   * Default key/value store service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactory
   */
  protected $keyValue;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\ConfigManager $config_manager
   *   Config manager service.
   * @param \Drupal\custom_configurations\CustomConfigurationsPluginManager $custom_configurations_plugin_manager
   *   C  ustom configurations plugin manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager service.
   * @param \Drupal\custom_configurations\CustomConfigurationsManager $custom_configurations_manager
   *   Config helper service.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactory $keyvalue
   *   Default key/value store service.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user.
   */
  public function __construct(ConfigFactory $config_factory, ConfigManager $config_manager, CustomConfigurationsPluginManager $custom_configurations_plugin_manager, LanguageManagerInterface $language_manager, CustomConfigurationsManager $custom_configurations_manager, KeyValueFactory $keyvalue, AccountProxy $current_user) {
    $this->configManager = $config_manager;
    $this->customConfigurationsPluginManager = $custom_configurations_plugin_manager;
    $this->languageManager = $language_manager;
    $this->customConfigurationsManager = $custom_configurations_manager;
    $this->keyValue = $keyvalue;
    $this->currentUser = $current_user;

    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.manager'),
      $container->get('plugin.manager.custom_configurations'),
      $container->get('language_manager'),
      $container->get('custom_configurations.manager'),
      $container->get('keyvalue'),
      $container->get('current_user')
    );
  }

  /**
   * Generates title dynamically.
   *
   * @param string $plugin_id
   *   Plugin id.
   * @param string $language
   *   Language id.
   *
   * @return array
   *   Render element.
   */
  public function titleCallback($plugin_id = NULL, $language = NULL) {
    $plugins = $this->customConfigurationsManager->getConfigPlugins();
    return [
      '#markup' => $this->t('@plugin_title (@lang_name)', [
        '@plugin_title' => isset($plugins[$plugin_id]) ? $plugins[$plugin_id]['title'] : '',
        '@lang_name' => $this->getLocaleTitle($language),
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    $configs = [];
    $plugins = $this->customConfigurationsManager->getConfigPlugins();
    foreach ($plugins as $plugin) {
      $configs[] = $this->customConfigurationsManager->getConfigKey($plugin['id']);
    }
    return $configs;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_configurations_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $plugin_id = NULL, $language = NULL) {

    // Store language in the form state for future reference.
    $form_state->setStorage([
      'language' => $language,
    ]);

    // $form['#cache'] = ['max-age' => 0];
    $plugins = $this->customConfigurationsManager->getConfigPlugins();
    // Fetch the plugin's form elements.
    $plugin_form = $this->processPluginForm($plugins[$plugin_id], $language);

    if (!empty($plugin_form)) {
      $form[$plugins[$plugin_id]['id']] = $plugin_form;
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    list($plugin_id, $language) = $form_state->getBuildInfo()['args'];

    $file_config = $this->getFileConfigStore($plugin_id, $language);
    $db_config = $this->getDbConfigStore($plugin_id, $language);

    $plugin = $this->customConfigurationsPluginManager->createInstance($plugin_id);
    $plugin->validate($file_config, $db_config, $form_state->getValues(), $form, $form_state, $language);

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    list($plugin_id, $language) = $form_state->getBuildInfo()['args'];

    $file_config = $this->getFileConfigStore($plugin_id, $language);
    $db_config = $this->getDbConfigStore($plugin_id, $language);
    $plugin = $this->customConfigurationsPluginManager->createInstance($plugin_id);
    $plugin->submit($file_config, $db_config, $values, $form, $form_state, $language);

    parent::submitForm($form, $form_state);
  }

  /**
   * Fetches the form elements from a config plugin and prepares output.
   *
   * @param array|string $definition
   *   The plugin definition.
   * @param string|null|false $language
   *   Representing the current language.
   *
   * @return array|false
   *   The resulting form elements.
   */
  protected function processPluginForm($definition, $language) {
    $form = [];
    if (!empty($definition['description'])) {
      $form['form_description'] = [
        '#markup' => '<i>' . $definition['description'] . '</i>',
      ];
    }
    // If a plugin ID is provided, fetch the full definition.
    if (is_string($definition)) {
      $definition = $this->customConfigurationsPluginManager->getDefinition($definition);
    }
    // Fetch the config for this particular locale.
    $file_config = $this->getFileConfigStore($definition['id'], $language);
    $db_config = $this->getDbConfigStore($definition['id'], $language);

    // Create an instance of the plugin...
    $plugin = $this->customConfigurationsPluginManager->createInstance($definition['id']);
    // Fetch the plugin's form elements...
    $el = $plugin->add($file_config, $db_config, $language);
    // Filter them for permissions and such...
    $this->filterElementPermissions($el);
    // ...then attach them to the overlying element!
    $form += $el;
    // Return the finalized form element, whether it has content or not.
    return $form;
  }

  /**
   * Fetch a specific plugin config based on language.
   *
   * @param string $plugin_id
   *   Plugin ID.
   * @param string $language
   *   Language to fetch.
   *
   * @return object
   *   Return the config.
   */
  public function getFileConfigStore($plugin_id, $language) {
    // No need to add language to the key, system will split configs up automatically.
    $key = $this->customConfigurationsManager->getConfigKey($plugin_id);
    if ($language && $this->languageManager instanceof ConfigurableLanguageManagerInterface) {
      $language = $this->customConfigurationsManager->getApplicableLanguageObject($language);
      $config = $this->languageManager->getLanguageConfigOverride($language->getId(), $key);
    }
    else {
      $config = $this->configFactory()->getEditable($key);
    }
    return $config;
  }

  /**
   * Fetch a DB key-value plugin config based on language.
   *
   * @param string $plugin_id
   *   Plugin ID.
   * @param string $language
   *   Language to fetch.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   *   Returns db storage service.
   */
  public function getDbConfigStore($plugin_id, $language) {
    $key = $this->customConfigurationsManager->getConfigKey($plugin_id, $language);
    $db_config = $this->keyValue->get($key);
    return $db_config;
  }

  /**
   * Get the appropriate title for the current form.
   *
   * @param string|null $language
   *   The langcode representing the current language.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Returns a fitting title.
   */
  public function getFormTitle($language = NULL) {
    $locale_title = $this->getLocaleTitle($language);
    return $this->t('Configuration for locale "@locale"', ['@locale' => $locale_title]);
  }

  /**
   * Get the appropriate title for the current locale.
   *
   * @param string|null $language
   *   The langcode representing the current language.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|mixed|null|string
   *   A fitting title for the locale.
   */
  public function getLocaleTitle($language = NULL) {
    if ($language) {
      $language_object = $this->languageManager->getLanguage($language);
      if ($language_object) {
        $title = $language_object->getName();
      }
    }
    return isset($title) ? $title : $this->t('Global');
  }

  /**
   * Check form access permissions, filter out the unauthorized.
   *
   * @param array $el
   *   Form element tree.
   */
  protected function filterElementPermissions(array &$el) {
    $acct_roles = $this->currentUser->getRoles();
    // Check the root element.
    if (isset($el['#access_roles'])) {
      if (!array_intersect($el['#access_roles'], $acct_roles)) {
        $el['#access'] = FALSE;
        // If the root is forbidden, no need to check further.
        return;
      }
    }

    // Check sub-elements.
    foreach ($el as &$value) {
      if (is_array($value)) {
        if (isset($value['#access_roles']) && !array_intersect($value['#access_roles'], $acct_roles)) {
          // If we don't have the necessary roles, remove access.
          $value['#access'] = FALSE;
        }
      }
    }
  }

}
