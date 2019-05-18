<?php

namespace Drupal\domain_lang;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\domain\DomainInterface;
use Drupal\domain\DomainLoaderInterface;
use Drupal\domain_lang\Exception\DomainLangDomainNotFoundException;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\language\LanguageNegotiatorInterface;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUI;

/**
 * Domain language handling.
 */
class DomainLangHandler implements DomainLangHandlerInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The language negotiation method plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $negotiatorManager;

  /**
   * The language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The language negotiator.
   *
   * @var \Drupal\language\LanguageNegotiatorInterface
   */
  protected $languageNegotiator;

  /**
   * The domain loader.
   *
   * @var \Drupal\domain\DomainLoaderInterface
   */
  protected $domainLoader;

  /**
   * Constructs a new class object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $negotiator_manager
   *   The language negotiation methods plugin manager.
   * @param \Drupal\language\ConfigurableLanguageManagerInterface $language_manager
   *    The language manager.
   * @param \Drupal\language\LanguageNegotiatorInterface $language_negotiator
   *   The language negotiation methods manager.
   * @param \Drupal\domain\DomainLoaderInterface $domain_loader
   *   The domain loader service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PluginManagerInterface $negotiator_manager, ConfigurableLanguageManagerInterface $language_manager, LanguageNegotiatorInterface $language_negotiator, DomainLoaderInterface $domain_loader) {
    $this->configFactory = $config_factory;
    $this->negotiatorManager = $negotiator_manager;
    $this->languageManager = $language_manager;
    $this->languageNegotiator = $language_negotiator;
    $this->domainLoader = $domain_loader;
  }

  /**
   * Returns mutable configuration object for language types.
   *
   * @return \Drupal\Core\Config\Config
   *   The language types config object.
   */
  protected function getLanguageTypesConfig() {
    return $this->getEditableConfig('language.types');
  }

  /**
   * Returns editable config object by config name.
   *
   * @param string $config_name
   *   The name of the config object.
   *
   * @return \Drupal\Core\Config\Config
   *   Editable config object.
   */
  protected function getEditableConfig($config_name) {
    return $this->configFactory->getEditable($this->getDomainConfigName($config_name));
  }

  /**
   * {@inheritdoc}
   */
  public function getDomainConfigName($config_name, DomainInterface $domain = NULL) {
    $domain = $domain ? $domain : $this->getDomainFromUrl();
    return 'domain.config.' . $domain->id() . '.' . $config_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getDomainFromUrl() {
    $domain_id = \Drupal::routeMatch()->getParameter('domain');

    if ($domain = $this->domainLoader->load($domain_id)) {
      return $domain;
    }

    throw new DomainLangDomainNotFoundException();
  }

  /**
   * {@inheritdoc}
   */
  public function updateConfiguration(array $types) {
    // Ensure that we are getting the defined language negotiation information.
    // An invocation of \Drupal\Core\Extension\ModuleInstaller::install() or
    // \Drupal\Core\Extension\ModuleInstaller::uninstall() could invalidate the
    // cached information.
    $this->negotiatorManager->clearCachedDefinitions();
    $this->languageManager->reset();

    $language_types = array();
    $language_types_info = $this->languageManager->getDefinedLanguageTypesInfo();
    $method_definitions = $this->languageNegotiator->getNegotiationMethods();

    foreach ($language_types_info as $type => $info) {
      $configurable = in_array($type, $types);

      // The default language negotiation settings, if available, are stored in
      // $info['fixed'].
      $has_default_settings = !empty($info['fixed']);
      // Check whether the language type is unlocked. Only the status of
      // unlocked language types can be toggled between configurable and
      // non-configurable.
      if (empty($info['locked'])) {
        if (!$configurable && !$has_default_settings) {
          // If we have an unlocked non-configurable language type without
          // default language negotiation settings, we use the values
          // negotiated for the interface language which, should always be
          // available.
          $method_weights = array(LanguageNegotiationUI::METHOD_ID);
          $method_weights = array_flip($method_weights);
          $this->saveConfiguration($type, $method_weights);
        }
      }
      else {
        // The language type is locked. Locked language types with default
        // settings are always considered non-configurable. In turn if default
        // settings are missing, the language type is always considered
        // configurable.
        // If the language type is locked we can just store its default language
        // negotiation settings if it has some, since it is not configurable.
        if ($has_default_settings) {
          $method_weights = array();
          // Default settings are in $info['fixed'].
          foreach ($info['fixed'] as $weight => $method_id) {
            if (isset($method_definitions[$method_id])) {
              $method_weights[$method_id] = $weight;
            }
          }
          $this->saveConfiguration($type, $method_weights);
        }
        else {
          // It was missing default settings, so force it to be configurable.
          $configurable = TRUE;
        }
      }

      // Accumulate information for each language type so it can be saved later.
      $language_types[$type] = $configurable;
    }

    // Store the language type configuration.
    $config = array(
      'configurable' => array_keys(array_filter($language_types)),
      'all' => array_keys($language_types),
    );
    $this->saveLanguageTypesConfiguration($config);
  }

  /**
   * {@inheritdoc}
   */
  public function saveLanguageTypesConfiguration(array $values) {
    $config = $this->getLanguageTypesConfig();
    if (isset($values['configurable'])) {
      $config->set('configurable', $values['configurable']);
    }
    if (isset($values['all'])) {
      $config->set('all', $values['all']);
    }
    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  public function saveConfiguration($type, array $enabled_methods) {
    // As configurable language types might have changed, we reset the cache.
    $this->languageManager->reset();
    $definitions = $this->languageNegotiator->getNegotiationMethods();
    $default_types = $this->languageManager->getLanguageTypes();

    // Order the language negotiation method list by weight.
    asort($enabled_methods);
    foreach ($enabled_methods as $method_id => $weight) {
      if (isset($definitions[$method_id])) {
        $method = $definitions[$method_id];
        // If the language negotiation method does not express any preference
        // about types, make it available for any configurable type.
        $types = array_flip(!empty($method['types']) ? $method['types'] : $default_types);
        // Check whether the method is defined and has the right type.
        if (!isset($types[$type])) {
          unset($enabled_methods[$method_id]);
        }
      }
      else {
        unset($enabled_methods[$method_id]);
      }
    }
    $this->getLanguageTypesConfig()->set('negotiation.' . $type . '.enabled', $enabled_methods)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getNegotiationMethods($type = NULL) {
    $definitions = $this->negotiatorManager->getDefinitions();
    if (isset($type)) {
      $enabled_methods = $this->getLanguageTypesConfig()->get('negotiation.' . $type . '.enabled') ?: array();
      $definitions = array_intersect_key($definitions, $enabled_methods);
    }
    return $definitions;
  }

}
