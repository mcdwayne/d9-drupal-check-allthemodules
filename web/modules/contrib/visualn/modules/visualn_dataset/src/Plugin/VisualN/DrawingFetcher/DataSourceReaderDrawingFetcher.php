<?php

namespace Drupal\visualn_dataset\Plugin\VisualN\DrawingFetcher;

use Drupal\visualn\Plugin\GenericDrawingFetcherBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\visualn\Manager\DrawerManager;
use Drupal\visualn\BuilderService;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Data source reader' VisualN drawing fetcher.
 *
 * @ingroup fetcher_plugins
 *
 * @VisualNDrawingFetcher(
 *  id = "visualn_data_source_reader",
 *  label = @Translation("Data source reader")
 * )
 */
class DataSourceReaderDrawingFetcher extends GenericDrawingFetcherBase {

  /**
   * The visualn resource format manager service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $visualNDataSourceStorage;

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
      $container->get('entity_type.manager')->getStorage('visualn_data_source')
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
   * @param \Drupal\Core\Entity\EntityStorageInterface $visualn_data_source_storage
   *   The visualn data source storage service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $visualn_style_storage, DrawerManager $visualn_drawer_manager, BuilderService $visualn_builder, EntityStorageInterface $visualn_data_source_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $visualn_style_storage, $visualn_drawer_manager, $visualn_builder);

    $this->visualNDataSourceStorage = $visualn_data_source_storage;
  }


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'visualn_data_source_id' => '',
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
    $visualn_dataset = visualn_data_source_options(FALSE);
    // Resource providers with required contexts are not included here (since they are not
    // included into the list of resource providers when configuring data source).

    $ajax_wrapper_id = implode('-', array_merge($form['#array_parents'], ['visualn_data_source_id'])) .'-ajax-wrapper';

    $form['visualn_data_source_id'] = [
      '#type' => 'select',
      '#title' => t('Data source'),
      '#description' => t('The data source for the drawing'),
      '#default_value' => $this->configuration['visualn_data_source_id'],
      '#options' => $visualn_dataset,
      '#required' => TRUE,
      '#empty_value' => '',
      '#empty_option' => t('- Select data source -'),
      // @todo: ajax needed only if config form is shown
      /*
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallbackDataSource'],
        'wrapper' => $ajax_wrapper_id,
      ],
      */
    ];

    /*
    $form['provider_container'] = [
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#type' => 'container',
      //'#process' => [[get_called_class(), 'processProviderContainerSubform']],
      //'#process' => [[$this, 'processSourceContainerSubform']],
    ];
    $form['provider_container']['#stored_configuration'] = $this->configuration;
    */

    // @todo: attach resource provider configuration form to allow to override data source configuration

    // @todo: maybe add a checkbox to use defaults from data source configuration


    // Attach visualn style select box for the fetcher
    $form += parent::buildConfigurationForm($form, $form_state);

    return $form;
  }


  /**
   * Return resource provider configuration form via ajax request at style change.
   * Should have a different name since ajaxCallback can be used by base class.
   */
  public static function ajaxCallbackDataSource(array $form, FormStateInterface $form_state, Request $request) {
    $triggering_element = $form_state->getTriggeringElement();
    $visualn_style_id = $form_state->getValue($form_state->getTriggeringElement()['#parents']);
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element['provider_container'];
  }


  /**
   * {@inheritdoc}
   */
  public function fetchDrawing() {
    $visualn_data_source_id = $this->configuration['visualn_data_source_id'];
    if (empty($visualn_data_source_id)) {
      return parent::fetchDrawing();
    }

    $visualn_data_source = $this->visualNDataSourceStorage->load($visualn_data_source_id);
    $resource_provider_id = $visualn_data_source->getResourceProviderId();
    $resource_provider_config = $visualn_data_source->getResourceProviderConfig();

    $fetcher_id = 'visualn_resource_provider_generic';
    $fetcher_config = [
      'resource_provider_id' => $resource_provider_id,
      'resource_provider_config' => $resource_provider_config,

      'visualn_style_id' => $this->configuration['visualn_style_id'],
      'drawer_config' => $this->configuration['drawer_config'],
      'drawer_fields' => $this->configuration['drawer_fields'],
    ];

    // @todo: maybe move into a trait
    // @todo: just reuse ResourceProviderGenericDrawingFetcher::fetchDrawing()
    $visualNDrawingFetcherManager = \Drupal::service('plugin.manager.visualn.drawing_fetcher');
    $fetcher_plugin = $visualNDrawingFetcherManager->createInstance($fetcher_id, $fetcher_config);

    // Set drawing window parameters
    $fetcher_plugin->setWindowParameters($this->getWindowParameters());

    return $fetcher_plugin->fetchDrawing();
  }

}
