<?php

namespace Drupal\visualn\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\visualn\Manager\DrawerManager;
use Drupal\visualn\Manager\DataGeneratorManager;
use Drupal\visualn\Manager\ResourceProviderManager;
use Drupal\Core\Form\SubformState;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\visualn\BuilderService;

/**
 * Class DrawerPreviewForm.
 */
class DrawerPreviewForm extends FormBase {

  // resource provider plugin id for generated data resource provider
  const RESOURCE_PROVIDER_ID = 'visualn_drawer_preview';


  /**
   * Drupal\visualn\Manager\DrawerManager definition.
   *
   * @var \Drupal\visualn\Manager\DrawerManager
   */
  protected $visualNDrawerManager;


  protected $visualNDataGeneratorManager;

  /**
   * The visualn resource format manager service.
   *
   * @var \Drupal\visualn\Manager\ResourceProviderManager
   */
  protected $visualNResourceProviderManager;

  /**
   * The visualn builder service.
   *
   * @var \Drupal\visualn\BuilderService
   */
  protected $visualNBuilder;

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.visualn.drawer'),
      $container->get('plugin.manager.visualn.data_generator'),
      $container->get('plugin.manager.visualn.resource_provider'),
      $container->get('visualn.builder')
    );
  }

  /**
   * Constructs a new DrawerPreviewForm object.
   */
  public function __construct(
      DrawerManager $plugin_manager_visualn_drawer,
      DataGeneratorManager $plugin_manager_visualn_data_generator,
      ResourceProviderManager $visualn_resource_provider_manager,
      BuilderService $visualn_builder
  ) {
    $this->visualNDrawerManager = $plugin_manager_visualn_drawer;
    $this->visualNDataGeneratorManager = $plugin_manager_visualn_data_generator;
    $this->visualNResourceProviderManager = $visualn_resource_provider_manager;
    $this->visualNBuilder = $visualn_builder;
  }



  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'visualn_drawer_preview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $base_drawer_id = $form_state->getBuildInfo()['args'][0];
    $drawer_config = [];

    $drawer_plugin = $this->visualNDrawerManager->createInstance($base_drawer_id, $drawer_config);

    $drawer_description = $drawer_plugin->getDescription();
    $form['drawer_description'] = [
      '#markup' => t('<strong>Description</strong><br /> @description', ['@description' => $drawer_description]),
      '#prefix' => '<div>',
      '#suffix' => '</div>',
    ];

    // use '#process' to get '#parents' and '#array_parets' arrays
    //   though also can be set manually here without useing '#process' callback
    $form['drawer_config'] = [
      '#tree' => TRUE,
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Drawer config'),
      '#process' => [[$this, 'processBaseDrawerSubform']],
      '#weight' => 2,
    ];

    $form['drawer_config']['#drawer_plugin'] = $drawer_plugin;

    // @todo: implement select/autocomplete form element for generator selector
    //   see visualn config form

    // Attach resource provider configuration form
    $resource_provider_id = self::RESOURCE_PROVIDER_ID;
    $resource_provider_plugin = $this->visualNResourceProviderManager->createInstance($resource_provider_id, []);

    $visualn_drawer_id_context = new Context(new ContextDefinition('string', NULL, TRUE), $base_drawer_id);
    $resource_provider_plugin->setContext('visualn_drawer_id', $visualn_drawer_id_context);
    $form['resource_provider_config'] = [
      '#tree' => TRUE,
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Resource provider config'),
      '#process' => [[$this, 'processResourceProviderSubform']],
      '#weight' => 4,
    ];
    $form['resource_provider_config']['#resource_provider_plugin'] = $resource_provider_plugin;

    // using '#process' callback to have form_state values ready
    $form['drawing_build'] = [
      '#prefix' => '<div id="drawing-build-ajax-wrapper">',
      '#suffix' => '</div>',
      '#type' => 'container',
      '#process' => [[$this, 'processDrawingBuild']],
      '#weight' => 1,
    ];

    // @todo: add configuration to change viewport to check responsive settings


    // show button only if user has permission to create styles
    $user = \Drupal::currentUser();
    // @todo: review permission checked here after changes to VisualNStyle entity permissions
    if ($user->hasPermission('administer site configuration')) {
      // create visualn style based on current drawer configuration
      $form['create_style'] = [
        '#type' => 'submit',
        '#value' => $this->t('Create style'),
        '#weight' => 5,
        '#submit' => ['::submitCreateStyle'],
      ];
    }


    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Redraw'),
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallback'],
        'wrapper' => 'drawing-build-ajax-wrapper',
      ],
      '#weight' => 1,
    ];

    $form['#pre_render'][] = static::class . '::preRender';

    $form['#attached']['library'][] = 'visualn/visualn-drawer-preview';

    return $form;
  }

  public function processBaseDrawerSubform(array $element, FormStateInterface $form_state, $form) {
    $drawer_plugin = $element['#drawer_plugin'];
    $subform_state = SubformState::createForSubform($element, $form, $form_state);
    $element = $drawer_plugin->buildConfigurationForm($element, $subform_state);

    return $element;
  }

/*
  public function processDataGeneratorSubform(array $element, FormStateInterface $form_state, $form) {
    $data_generator_plugin = $element['#data_generator_plugin'];
    $subform_state = SubformState::createForSubform($element, $form, $form_state);
    $element = $data_generator_plugin->buildConfigurationForm($element, $subform_state);

    return $element;
  }
*/

  public function processResourceProviderSubform(array $element, FormStateInterface $form_state, $form) {
    $resource_provider_plugin = $element['#resource_provider_plugin'];
    $subform_state = SubformState::createForSubform($element, $form, $form_state);
    $element = $resource_provider_plugin->buildConfigurationForm($element, $subform_state);
    //$element['data_generator_id']['#default_value'] = self::DATA_GENERATOR_ID;

    return $element;
  }

  public function processDrawingBuild(array $element, FormStateInterface $form_state, $form) {
    // Instead of getting values directy from $form_state->getValue('drawer_config', []),
    // use plugin extractFormValues() method since config structure may differ from
    // config form structure and thus the config form values structure.
    $drawer_plugin = $form['drawer_config']['#drawer_plugin'];
    $subform_state = SubformState::createForSubform($form['drawer_config'], $form, $form_state);
    $drawer_config = $drawer_plugin->extractFormValues($form['drawer_config'], $subform_state);


    $provider_plugin = $form['resource_provider_config']['#resource_provider_plugin'];
    $provider_config = $form_state->getValue('resource_provider_config');
    $provider_plugin->setConfiguration($provider_config);
    // @todo: draw drawing in pre_render callback otherwise it is called twice
    $resource = $provider_plugin->getResource();


    // @todo: visualn style isn't used here, base_drawer_id is passed as an additional argument instead
    //   review and clean-up ::makeBuildByResource() implementation
    $visualn_style_id = '';
    $drawer_fields = [];
    $base_drawer_id = $form_state->getBuildInfo()['args'][0];
    $build = $this->visualNBuilder->makeBuildByResource($resource, $visualn_style_id, $drawer_config, $drawer_fields, $base_drawer_id);

    $element['build'] = $build;

    return $element;
  }


  public static function preRender($build) {
    if (isset($build['resource_provider_config']['data_generator_id']['#value'])) {
      $generator_id = $build['resource_provider_config']['data_generator_id']['#value'];
      // open data generator configuration form by default
      if (isset($build['resource_provider_config']['generator_container'][$generator_id]['#open'])) {
        $build['resource_provider_config']['generator_container'][$generator_id]['#open'] = TRUE;
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitCreateStyle(array &$form, FormStateInterface $form_state) {

    // store drawer id and config values for creating style
    $tempstore = \Drupal::service('tempstore.private')->get('visualn_drawer_preview');

    // generate unique id for drawer_id and drawer_config storage
    $uuid = \Drupal::service('uuid')->generate();
    $uuid = substr($uuid, 0, 8);

    $base_drawer_id = $form_state->getBuildInfo()['args'][0];
    $drawer_config = $form_state->getValue('drawer_config', []);

    $tempstore->set($uuid, [
      'drawer_id' => $base_drawer_id,
      'drawer_config' => $drawer_config,
    ]);

    $form_state->setRedirect('entity.visualn_style.add_form', [], ['query' => ['drawer-preview' => $uuid]]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo: trigger drawer plugin ::validateConfigurationForm() and ::submitConfigurationForm() methods
    //   e.g. to clean values
    //   same for data_generator plugins

    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      //drupal_set_message($key . ': ' . $value);
    }

    $form_state->setRebuild(TRUE);

  }

  public static function ajaxCallback(array $form, FormStateInterface $form_state, Request $request) {
/*
    $triggering_element = $form_state->getTriggeringElement();
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element['drawer_container'];
*/
    //return ['#markup' => 'abc'];

    return $form['drawing_build'];
  }

}
