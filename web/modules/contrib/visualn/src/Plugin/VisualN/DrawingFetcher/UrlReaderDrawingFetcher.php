<?php

namespace Drupal\visualn\Plugin\VisualN\DrawingFetcher;

use Drupal\visualn\Plugin\GenericDrawingFetcherBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\visualn\Manager\DrawerManager;
use Drupal\visualn\Manager\RawResourceFormatManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
//use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\visualn\Helpers\VisualNFormsHelper;
use Drupal\visualn\Helpers\VisualN;
use Drupal\visualn\BuilderService;

/**
 * Provides a 'Url reader' VisualN drawing fetcher.
 *
 * @ingroup fetcher_plugins
 *
 * @VisualNDrawingFetcher(
 *  id = "visualn_url_reader",
 *  label = @Translation("Url reader")
 * )
 */
class UrlReaderDrawingFetcher extends GenericDrawingFetcherBase {

  const RAW_RESOURCE_FORMAT_GROUP = 'visualn_url_widget';

  // @todo: this is to avoid the error: "LogicException: The database connection is not serializable.
  // This probably means you are serializing an object that has an indirect reference to the database connection.
  // Adjust your code so that is not necessary. Alternatively, look at DependencySerializationTrait
  // as a temporary solution." when using from inside VisualNFetcherWidget
  //use DependencySerializationTrait;


  /**
   * The visualn resource format manager service.
   *
   * @var \Drupal\visualn\Manager\RawResourceFormatManager
   */
  protected $visualNResourceFormatManager;

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
      $container->get('plugin.manager.visualn.raw_resource_format')
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
   * @param \Drupal\visualn\Manager\RawResourceFormatManager $visualn_resource_format_manager
   *   The visualn resource format manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $visualn_style_storage, DrawerManager $visualn_drawer_manager, BuilderService $visualn_builder, RawResourceFormatManager $visualn_resource_format_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $visualn_style_storage, $visualn_drawer_manager, $visualn_builder);

    $this->visualNResourceFormatManager = $visualn_resource_format_manager;
  }


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'resource_url' => '',
      'resource_format' => '',
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

    // @todo: validate the url
    $form['resource_url'] = [
      '#type' => 'textfield',
      '#title' => t('Resource Url'),
      '#description' => t('Resource URL to use as data source for the drawing'),
      '#default_value' => $this->configuration['resource_url'],
      '#maxlength' => 256,
      '#size' => 64,
      '#required' => TRUE,
    ];

    // Get resource formats plugins list for the resource formats select.
    $resource_formats = [];
    $definitions = VisualN::getRawResourceFormatsByGroup(self::RAW_RESOURCE_FORMAT_GROUP);
    foreach ($definitions as $definition) {
      $resource_formats[$definition['id']] = $definition['label'];
    }

    $form['resource_format'] = [
      '#type' => 'select',
      '#title' => t('Resource format'),
      '#description' => t('The format of the data source'),
      '#default_value' => $this->configuration['resource_format'],
      '#options' => $resource_formats,
      '#empty_value' => '',
      '#empty_option' => t('- Select resource format -'),
    ];

    // Attach visualn style select box for the fetcher
    $form += parent::buildConfigurationForm($form, $form_state);

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function fetchDrawing() {
    // @todo: review the code here
    $drawing_markup = parent::fetchDrawing();

    $url = $this->configuration['resource_url'];
    $visualn_style_id = $this->configuration['visualn_style_id'];
    if (empty($visualn_style_id)) {
      return $drawing_markup;
    }


    if (!empty($this->configuration['resource_format'])) {
      // @todo: unsupported operand types error
      //    add default value into defaultConfiguration()
      $drawer_config = $this->configuration['drawer_config'] ?: [];
      $drawer_fields = $this->configuration['drawer_fields'];

      $raw_resource_plugin_id = $this->configuration['resource_format'];
      $raw_input = [
        'file_url' => $this->configuration['resource_url'],
        // @todo: this should be detected dynamically depending on reousrce type, headers, file extension
        //'file_mimetype' => 'text/tab-separated-values',
      ];
      $resource =
        $this->visualNResourceFormatManager->createInstance($raw_resource_plugin_id, [])
        ->buildResource($raw_input);

      // Get drawing window parameters
      $window_parameters = $this->getWindowParameters();

      // Get drawing build
      $build = $this->visualNBuilder->makeBuildByResource($resource, $visualn_style_id, $drawer_config, $drawer_fields, '', $window_parameters);

      $drawing_markup = $build;
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
    // @todo: validate configuration form: resource_url
    parent::validateConfigurationForm($form, $form_state);
  }

}

