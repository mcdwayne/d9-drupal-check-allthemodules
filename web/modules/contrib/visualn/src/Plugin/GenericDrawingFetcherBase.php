<?php

namespace Drupal\visualn\Plugin;

use Drupal\visualn\Core\DrawingFetcherBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\visualn\Manager\DrawerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\visualn\Helpers\VisualNFormsHelper;
use Drupal\visualn\BuilderService;

abstract class GenericDrawingFetcherBase extends DrawingFetcherBase implements ContainerFactoryPluginInterface {

  // @todo: this is to avoid the error: "LogicException: The database connection is not serializable.
  // This probably means you are serializing an object that has an indirect reference to the database connection.
  // Adjust your code so that is not necessary. Alternatively, look at DependencySerializationTrait
  // as a temporary solution." when using from inside VisualNFetcherWidget
  // @todo: commented since ContextAwarePluginBase implements it by itself, though it was introduced into fetchers before
  //    using contexts so the reason should be explored first before deleting this comment and explicitly motivate usage.
  //use DependencySerializationTrait;

  /**
   * The image style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $visualNStyleStorage;

  /**
   * The visualn drawer manager service.
   *
   * @var \Drupal\visualn\Manager\DrawerManager
   */
  protected $visualNDrawerManager;

  /**
   * The visualn builder service.
   *
   * @var \Drupal\visualn\BuilderService
   */
  protected $visualNBuilder;

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
      $container->get('visualn.builder')
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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityStorageInterface $visualn_style_storage, DrawerManager $visualn_drawer_manager, BuilderService $visualn_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->visualNStyleStorage = $visualn_style_storage;
    $this->visualNDrawerManager = $visualn_drawer_manager;
    $this->visualNBuilder = $visualn_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'visualn_style_id' => '',
      'drawer_config' => [],
      'drawer_fields' => [],
    ] + parent::defaultConfiguration();

 }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $visualn_style_id = $form_state->getValue('visualn_style_id');

    // @todo: how to check if the form is fresh
    // is null basically means that the form is fresh (maybe check the whole $form_state->getValues() to be sure?)
    // $visualn_style_id can be empty string (in case of default choice) or NULL in case of fresh form

    if (is_null($visualn_style_id)) {
      $visualn_style_id = $this->configuration['visualn_style_id'];
    }



    // Attach visualn style select
    $visualn_styles = visualn_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
      t('Configure VisualN Styles'),
      Url::fromRoute('entity.visualn_style.collection')
    );



    // @todo: so we can use #array_parents to create a unique wrapper or store it even in form_state->addBuildInfo()
    //    also keyed by #array_parents since there may be multiple fetcher plugins forms on a page (e.g. entity fields)
    //    or even store as a hidden element and get it from form_state->getValues()
    $ajax_wrapper_id = implode('-', array_merge($form['#array_parents'], ['visualn_style_id'])) .'-ajax-wrapper';


    $form['visualn_style_id'] = [
      '#type' => 'select',
      '#title' => t('VisualN style'),
      '#options' => $visualn_styles,
      '#default_value' => $visualn_style_id,
      '#description' => t('Default style for the data to render.'),
      // @todo: add permission check for current user
      '#description' => $description_link->toRenderable() + [
        //'#access' => $this->currentUser->hasPermission('administer visualn styles')
        '#access' => TRUE
      ],
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallback'],
        'wrapper' => $ajax_wrapper_id,
      ],
      '#required' => TRUE,
      '#empty_value' => '',
      '#empty_option' => t('- Select visualization style -'),
    ];
    $form['drawer_container'] = [
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#type' => 'container',
      //'#process' => [[get_called_class(), 'processDrawerContainerSubform']],
      '#process' => [[$this, 'processDrawerContainerSubform']],
    ];
    $form['drawer_container']['#stored_configuration'] = $this->configuration;

    return $form;
  }

  /**
   * Return drawer configuration form via ajax request at style change
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state, Request $request) {
    $triggering_element = $form_state->getTriggeringElement();
    $visualn_style_id = $form_state->getValue($form_state->getTriggeringElement()['#parents']);
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element['drawer_container'];
  }

  // @todo: this should be static since may not work on field settings form (see fetcher field widget for example)
  //public static function processDrawerContainerSubform(array $element, FormStateInterface $form_state, $form) {
  public function processDrawerContainerSubform(array $element, FormStateInterface $form_state, $form) {
    $stored_configuration = $element['#stored_configuration'];
    $configuration = [
      'visualn_style_id' => $stored_configuration['visualn_style_id'],
      'drawer_config' => $stored_configuration['drawer_config'],
      'drawer_fields' => $stored_configuration['drawer_fields'],
    ];

    $element = VisualNFormsHelper::processDrawerContainerSubform($element, $form_state, $form, $configuration);

    return $element;
  }


  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // @todo: extract and restructure data fields
    //    at the moment this is done on the validation level which is not correct,
    //    also it leaves an empty 'drawer_container' key in form_state->getValues()
    //    (though removes drawer_container_key)
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // @todo: validate configuration form: resource_url
  }

}

