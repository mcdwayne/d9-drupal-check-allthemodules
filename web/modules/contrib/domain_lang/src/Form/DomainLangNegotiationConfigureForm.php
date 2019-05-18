<?php

namespace Drupal\domain_lang\Form;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Url;
use Drupal\domain_lang\DomainLangHandlerInterface;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\language\Form\NegotiationConfigureForm;
use Drupal\language\LanguageNegotiatorInterface;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationSelected;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Configure the selected language negotiation method for this site.
 */
class DomainLangNegotiationConfigureForm extends NegotiationConfigureForm {

  /**
   * The domain lang handler.
   *
   * @var \Drupal\domain_lang\DomainLangHandlerInterface
   */
  protected $domainLangHandler;

  /**
   * Route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Language mappings config name for current active domain.
   *
   * @var string
   */
  protected $languageTypesConfig;

  /**
   * Constructs a NegotiationConfigureForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\language\ConfigurableLanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\language\LanguageNegotiatorInterface $negotiator
   *   The language negotiation methods manager.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block manager.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Entity\EntityStorageInterface $block_storage
   *   The block storage, or NULL if not available.
   * @param \Drupal\domain_lang\DomainLangHandlerInterface $domain_lang_handler
   *   The domain lang handler.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   Route provider.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ConfigurableLanguageManagerInterface $language_manager, LanguageNegotiatorInterface $negotiator, BlockManagerInterface $block_manager, ThemeHandlerInterface $theme_handler, EntityStorageInterface $block_storage = NULL, DomainLangHandlerInterface $domain_lang_handler, RouteProviderInterface $route_provider) {
    parent::__construct($config_factory, $language_manager, $negotiator, $block_manager, $theme_handler, $block_storage);
    $this->domainLangHandler = $domain_lang_handler;
    $this->routeProvider = $route_provider;
    $this->languageTypesConfig = $this->domainLangHandler->getDomainConfigName('language.types');
    $this->languageTypes = $this->config($this->languageTypesConfig);

    // Fill with initial values on first page visit.
    if ($this->languageTypes->isNew()) {
      $this->languageTypes->merge($this->config('language.types')->get());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    $block_storage = $entity_manager->hasHandler('block', 'storage') ? $entity_manager->getStorage('block') : NULL;
    return new static(
      $container->get('config.factory'),
      $container->get('language_manager'),
      $container->get('language_negotiator'),
      $container->get('plugin.manager.block'),
      $container->get('theme_handler'),
      $block_storage,
      $container->get('domain_lang.handler'),
      $container->get('router.route_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['language.types', $this->languageTypesConfig];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $configurable = $this->languageTypes->get('configurable');

    $form = array(
      '#theme' => 'language_negotiation_configure_form',
      '#language_types_info' => $this->languageManager->getDefinedLanguageTypesInfo(),
      '#language_negotiation_info' => $this->domainLangHandler->getNegotiationMethods(),
    );
    $form['#language_types'] = array();

    foreach ($form['#language_types_info'] as $type => $info) {
      // Show locked language types only if they are configurable.
      if (empty($info['locked']) || in_array($type, $configurable)) {
        $form['#language_types'][] = $type;
      }
    }

    foreach ($form['#language_types'] as $type) {
      $this->configureFormTable($form, $type);
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Save settings'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $configurable_types = $form['#language_types'];

    $stored_values = $this->languageTypes->get('configurable');
    $customized = array();
    $method_weights_type = array();

    foreach ($configurable_types as $type) {
      $customized[$type] = in_array($type, $stored_values);
      $method_weights = array();
      $enabled_methods = $form_state->getValue(array($type, 'enabled'));
      $enabled_methods[LanguageNegotiationSelected::METHOD_ID] = TRUE;
      $method_weights_input = $form_state->getValue(array($type, 'weight'));
      if ($form_state->hasValue(array($type, 'configurable'))) {
        $customized[$type] = !$form_state->isValueEmpty(array($type, 'configurable'));
      }

      foreach ($method_weights_input as $method_id => $weight) {
        if ($enabled_methods[$method_id]) {
          $method_weights[$method_id] = $weight;
        }
      }

      $method_weights_type[$type] = $method_weights;
      $this->languageTypes->set('negotiation.' . $type . '.method_weights', $method_weights_input)->save();
    }

    // Update non-configurable language types and the related language
    // negotiation configuration.
    $this->domainLangHandler->updateConfiguration(array_keys(array_filter($customized)));

    // Update the language negotiations after setting the configurability.
    foreach ($method_weights_type as $type => $method_weights) {
      $this->domainLangHandler->saveConfiguration($type, $method_weights);
    }

    // Clear block definitions cache since the available blocks and their names
    // may have been changed based on the configurable types.
    if ($this->blockStorage) {
      // If there is an active language switcher for a language type that has
      // been made not configurable, deactivate it first.
      $non_configurable = array_keys(array_diff($customized, array_filter($customized)));
      $this->disableLanguageSwitcher($non_configurable);
    }
    $this->blockManager->clearCachedDefinitions();

    drupal_set_message($this->t('Language detection configuration saved.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function configureFormTable(array &$form, $type) {
    $info = $form['#language_types_info'][$type];

    $table_form = array(
      '#title' => $this->t('@type language detection', array('@type' => $info['name'])),
      '#tree' => TRUE,
      '#description' => $info['description'],
      '#language_negotiation_info' => array(),
      '#show_operations' => FALSE,
      'weight' => array('#tree' => TRUE),
    );
    // Only show configurability checkbox for the unlocked language types.
    if (empty($info['locked'])) {
      $configurable = $this->languageTypes->get('configurable');
      $table_form['configurable'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Customize %language_name language detection to differ from Interface text language detection settings', array('%language_name' => $info['name'])),
        '#default_value' => in_array($type, $configurable),
        '#attributes' => array('class' => array('language-customization-checkbox')),
        '#attached' => array(
          'library' => array(
            'language/drupal.language.admin',
          ),
        ),
      );
    }

    $negotiation_info = $form['#language_negotiation_info'];
    $enabled_methods = $this->languageTypes->get('negotiation.' . $type . '.enabled') ?: array();
    $methods_weight = $this->languageTypes->get('negotiation.' . $type . '.method_weights') ?: array();

    // Add missing data to the methods lists.
    foreach ($negotiation_info as $method_id => $method) {
      if (!isset($methods_weight[$method_id])) {
        $methods_weight[$method_id] = isset($method['weight']) ? $method['weight'] : 0;
      }
    }

    // Order methods list by weight.
    asort($methods_weight);

    foreach ($methods_weight as $method_id => $weight) {
      // A language method might be no more available if the defining module has
      // been disabled after the last configuration saving.
      if (!isset($negotiation_info[$method_id])) {
        continue;
      }

      $enabled = isset($enabled_methods[$method_id]);
      $method = $negotiation_info[$method_id];

      // List the method only if the current type is defined in its 'types' key.
      // If it is not defined default to all the configurable language types.
      $types = array_flip(isset($method['types']) ? $method['types'] : $form['#language_types']);

      if (isset($types[$type])) {
        $table_form['#language_negotiation_info'][$method_id] = $method;
        $method_name = $method['name'];

        $table_form['weight'][$method_id] = array(
          '#type' => 'weight',
          '#title' => $this->t('Weight for @title language detection method', array('@title' => Unicode::strtolower($method_name))),
          '#title_display' => 'invisible',
          '#default_value' => $weight,
          '#attributes' => array('class' => array("language-method-weight-$type")),
          '#delta' => 20,
        );

        $table_form['title'][$method_id] = array('#plain_text' => $method_name);

        $table_form['enabled'][$method_id] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Enable @title language detection method', array('@title' => Unicode::strtolower($method_name))),
          '#title_display' => 'invisible',
          '#default_value' => $enabled,
        );
        if ($method_id === LanguageNegotiationSelected::METHOD_ID) {
          $table_form['enabled'][$method_id]['#default_value'] = TRUE;
          $table_form['enabled'][$method_id]['#attributes'] = array('disabled' => 'disabled');
        }

        $table_form['description'][$method_id] = array('#markup' => $method['description']);

        $config_op = array();
        if (isset($method['config_route_name'])) {
          $new_route = 'domain_lang.' . substr($method['config_route_name'], 9);

          try {
            $this->routeProvider->getRouteByName($new_route);
            $config_url = Url::fromRoute(
              $new_route,
              ['domain' => $this->domainLangHandler->getDomainFromUrl()->id()]
            );
          }
          catch (RouteNotFoundException $e) {
            $config_url = Url::fromRoute($method['config_route_name']);
          }

          $config_op['configure'] = array(
            'title' => $this->t('Configure'),
            'url' => $config_url,
          );
          // If there is at least one operation enabled show the operation
          // column.
          $table_form['#show_operations'] = TRUE;
        }
        $table_form['operation'][$method_id] = array(
          '#type' => 'operations',
          '#links' => $config_op,
        );
      }
    }
    $form[$type] = $table_form;
  }

}
