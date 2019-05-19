<?php

namespace Drupal\visualn\Plugin\VisualN\DrawingFetcher;

use Drupal\visualn\Plugin\GenericDrawingFetcherBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\visualn\Manager\DrawerManager;
use Drupal\visualn\Manager\ResourceProviderManager;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Render\Element;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\visualn\Helpers\VisualNFormsHelper;
use Drupal\visualn\BuilderService;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;

/**
 * Provides a 'Basic (resource provider switcher)' VisualN drawing fetcher.
 *
 * @ingroup fetcher_plugins
 *
 * @VisualNDrawingFetcher(
 *  id = "visualn_resource_provider_switcher",
 *  label = @Translation("Basic (resource provider switcher)"),
 *  context = {
 *    "entity_type" = @ContextDefinition("string", label = @Translation("Entity type"), required = FALSE),
 *    "bundle" = @ContextDefinition("string", label = @Translation("Bundle"), required = FALSE),
 *    "current_entity" = @ContextDefinition("any", label = @Translation("Current entity"), required = FALSE)
 *  }
 * )
 */
class ResourceProviderSwitcherDrawingFetcher extends GenericDrawingFetcherBase {

  /**
   * The visualn resource format manager service.
   *
   * @var \Drupal\visualn\Manager\ResourceProviderManager
   */
  protected $visualNResourceProviderManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')->getStorage('visualn_style'),
      $container->get('plugin.manager.visualn.drawer'),
      $container->get('visualn.builder'),
      $container->get('plugin.manager.visualn.resource_provider')
    );
  }

  /**
   * Constructs a VisualNFormatter object.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition
   * @param \Drupal\Core\Entity\EntityStorageInterface $visualn_style_storage
   *   The visualn style entity storage service.
   * @param \Drupal\visualn\Manager\DrawerManager $visualn_drawer_manager
   *   The visualn drawer manager service.
   * @param \Drupal\visualn\BuilderService $visualn_builder
   *   The visualn builder service.
   * @param \Drupal\visualn\Manager\ResourceProviderManager $visualn_resource_provider_manager
   *   The visualn resource provider manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $visualn_style_storage, DrawerManager $visualn_drawer_manager, BuilderService $visualn_builder, ResourceProviderManager $visualn_resource_provider_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $visualn_style_storage, $visualn_drawer_manager, $visualn_builder);

    $this->visualNResourceProviderManager = $visualn_resource_provider_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'resource_provider_id' => '',
      'resource_provider_config' => [],
      // these settings are provided by GenericDrawingFetcherBase abstract class
      //'visualn_style_id' => '',
      //'drawer_config' => [],
      //'drawer_fields' => [],
    ] + parent::defaultConfiguration();
  }


  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $definitions = $this->visualNResourceProviderManager->getDefinitions();
    $resource_providers = [];
    foreach ($definitions as $definition) {

      // Exclude providers which have at least one required context since here no context is provided.

      // Resource providers may have required contexts some of which are requried only when showing configuration
      // form and some are requried when creating a resource object or both.
      // This means that fetcher may allow user to select such resource providers even if resource generation
      // level required contexts are not there at configuration form level.
      // @todo: Though fetcher can't guess which contexts will be provided by environment when generating
      //   a resource. This issue should be sorted out.
      //   Maybe add a method that would provide the environment with info about optional and required contexts.
      if (!empty($definition['context'])) {
        foreach ($definition['context'] as $name => $context_definition) {
          // @todo: Here we check only contexts required for the form (e.g. we don't check "current_entity" context)
          //    though it may be required for getResourceProviderPlugin() method to work. We suppose that
          //    only "entity_type" and "bundle" are enough here (which generally may be wrong).
          //    Also the "current_entity" context seems to not being checked anywhere and is supposed to work
          //    by convention.
          if (!in_array($name, array('entity_type', 'bundle'))) {
            if ($context_definition->isRequired() && $name != 'current_entity') {
              continue 2;
            }
            continue;
          }
          elseif ($context_definition->isRequired() && !$this->getContextValue($name)) {
            continue 2;
          }
        }
      }

      // Use only providers that have matching contexts or the contexts that are expected
      // to be there when generating a resource.
      // See the notice in @todo: above.
      $resource_providers[$definition['id']] = $definition['label'];
    }

    $ajax_wrapper_id = implode('-', array_merge($form['#array_parents'], ['resource_provider_id'])) .'-ajax-wrapper';

    $form['resource_provider_id'] = [
      '#type' => 'select',
      '#title' => t('Resource provider'),
      '#description' => t('Providers prepare a resource object based on files, urls, data generators etc.'),
      '#default_value' => $this->configuration['resource_provider_id'],
      '#options' => $resource_providers,
      '#required' => TRUE,
      '#empty_value' => '',
      '#empty_option' => t('- Select resource provider -'),
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallbackResourceProvider'],
        'wrapper' => $ajax_wrapper_id,
      ],
    ];
    $form['provider_container'] = [
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#type' => 'container',
      //'#process' => [[get_called_class(), 'processProviderContainerSubform']],
      '#process' => [[$this, 'processProviderContainerSubform']],
    ];
    $form['provider_container']['#stored_configuration'] = $this->configuration;

    // @todo: no need to set this contexts for resource provider plugins that don't have
    //    them in their annotation (plugin definition), e.g. random data generator plugins.
    // @todo: a similar note should be added to settings context for
    //    generic fetcher plugins (e.g. resource generic fetcher which doesn't need any context to work).
    $form['provider_container']['#entity_type'] = $this->getContextValue('entity_type');
    // @todo: use #entity_bundle key for consistency
    $form['provider_container']['#bundle'] = $this->getContextValue('bundle');

    // Attach visualn style select box for the fetcher
    $form += parent::buildConfigurationForm($form, $form_state);

    return $form;
  }

  /**
   * Return resource provider configuration form via ajax request at style change.
   * Should have a different name since ajaxCallback can be used by base class.
   */
  public static function ajaxCallbackResourceProvider(array $form, FormStateInterface $form_state, Request $request) {
    $triggering_element = $form_state->getTriggeringElement();
    $visualn_style_id = $form_state->getValue($form_state->getTriggeringElement()['#parents']);
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element['provider_container'];
  }

  // @todo: this should be static since may not work on field settings form (see fetcher field widget for example)
  //public static function processDrawerContainerSubform(array $element, FormStateInterface $form_state, $form) {
  public function processProviderContainerSubform(array $element, FormStateInterface $form_state, $form) {
    // @todo: explicitly set #stored_configuration and other keys (#entity_type and #bundle) here
    $element = VisualNFormsHelper::doProcessProviderContainerSubform($element, $form_state, $form);
    return $element;
  }


  /**
   * {@inheritdoc}
   */
  public function fetchDrawing() {
    //dsm($this->configuration);

    $resource_provider_id = $this->configuration['resource_provider_id'];
    $resource_provider_config = $this->configuration['resource_provider_config'];
    $visualn_style_id = $this->configuration['visualn_style_id'];
    //if (empty($visualn_style_id) || empty($resource_provider_id)) {
    if (empty($visualn_style_id)) {
      return parent::fetchDrawing();
    }

    $build = [];


    // @todo:

    if (!empty($resource_provider_id)) {
      $provider_plugin = $this->visualNResourceProviderManager->createInstance($resource_provider_id, $resource_provider_config);

      // @todo: maybe $build['drawing'] should be passed to manager but not the $build itself

      $current_entity = $this->getContextValue('current_entity');

      // @todo: replace "any" context type with an appropriate one
      // Set "current_entity" context
      $context_current_entity = new Context(new ContextDefinition('any', NULL, TRUE), $current_entity);
      $provider_plugin->setContext('current_entity', $context_current_entity);
      // @todo: see the note regarding setting context in VisualNResourceProviderItem class

      $drawer_config = $this->configuration['drawer_config'];
      $drawer_fields = $this->configuration['drawer_fields'];

      $resource = $provider_plugin->getResource();

      // Get drawing window parameters
      $window_parameters = $this->getWindowParameters();

      // Get drawing build
      $build = $this->visualNBuilder->makeBuildByResource($resource, $visualn_style_id, $drawer_config, $drawer_fields, '', $window_parameters);

      $drawing_markup = $build;

      // Every resource type is a Typed Data object so it may have its own fixed set of propertries and
      // has validation callback to check if everything is set as expected.
    }



    return $drawing_markup;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
  }
}

// @todo: add comments everywhere (in particlar to #process callback method code)

