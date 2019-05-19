<?php

namespace Drupal\xy_grid_layouts\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\breakpoint\BreakpointManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Layout class for all Foundation layouts.
 */
class XyGridLayouts extends LayoutDefault implements PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * The Config Factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Breakpoint Manager.
   *
   * @var \Drupal\breakpoint\BreakpointManagerInterface
   */
  protected $breakpointManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, BreakpointManagerInterface $breakpoint_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->breakpointManager = $breakpoint_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('breakpoint.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'wrappers' => [],
      'grid' => [
        'type' => 'grid-x',
      ],
      'grid_container' => [],
      'breakpoint' => 'medium',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();
    $regions = $this->getPluginDefinition()->getRegions();

    // Breakpoint.
    if ($this->moduleHandler->moduleExists('breakpoint')) {
      $front_theme = $this->configFactory->get('system.theme')->get('default');
      $breakpoints = $this->breakpointManager->getBreakpointsByGroup($front_theme);
      $breakpoints_labels = [];
      foreach ($breakpoints as $breakpoint) {
        $breakpoint_label = (string) $breakpoint->getLabel();
        $breakpoints_labels[$breakpoint_label] = $breakpoint_label;
      }
      $form['breakpoint'] = [
        '#type' => 'select',
        '#title' => $this->t('Breakpoint'),
        '#description' => $this->t('The layout will be applied from this breakpoint.'),
        '#default_value' => $configuration['breakpoint'],
        '#options' => $breakpoints_labels,
      ];
    }

    $form['grid_container'] = [
      '#group' => 'additional_settings',
      '#type' => 'details',
      '#title' => $this->t('Grid container'),
      '#description' => $this->t('Attributes for grid container'),
      '#tree' => TRUE,
    ];
    $form['grid_container']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#default_value' => $configuration['grid_container']['type'],
      '#empty_option' => $this->t('Default'),
      '#empty_value' => '',
      '#options' => [
        'fluid' => $this->t('Fluid'),
        'full' => $this->t('Full'),
      ],
    ];
    $form['grid_container']['classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Classes'),
      '#description' => $this->t('Add additional classes to the grid container element.'),
      '#default_value' => $configuration['grid_container']['classes'],
      '#weight' => 1,
    ];
    $form['grid_container']['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Id'),
      '#description' => $this->t('Add an Id to the grid container element.'),
      '#default_value' => $configuration['grid_container']['id'],
      '#weight' => 1,
    ];

    $form['grid'] = [
      '#group' => 'additional_settings',
      '#type' => 'details',
      '#title' => $this->t('Grid'),
      '#description' => $this->t('Attributes for the grid element'),
      '#tree' => TRUE,
    ];
    $form['grid']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#default_value' => $configuration['grid']['type'],
      '#options' => [
        'grid-x' => $this->t('Grid X'),
        'grid-y' => $this->t('Grid Y'),
      ],
    ];
    $form['grid']['classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Classes'),
      '#description' => $this->t('Add additional classes to the grid element.'),
      '#default_value' => $configuration['grid']['classes'],
    ];
    $form['grid']['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Id'),
      '#description' => $this->t('Add an Id to the grid element.'),
      '#default_value' => $configuration['grid']['id'],
    ];

    // Add wrappers.
    $wrapper_options = [
      'div' => 'Div',
      'section' => 'Section',
      'header' => 'Header',
      'footer' => 'Footer',
      'aside' => 'Aside',
    ];

    $form['region_wrapper'] = [
      '#group' => 'additional_settings',
      '#type' => 'details',
      '#title' => $this->t('Custom wrappers'),
      '#description' => $this->t('Choose a wrapper'),
      '#tree' => TRUE,
    ];

    foreach ($regions as $region_name => $region_definition) {
      $form['region_wrapper'][$region_name] = [
        '#type' => 'select',
        '#options' => $wrapper_options,
        '#title' => $this->t('Wrapper for @region', ['@region' => $region_definition['label']]),
        '#default_value' => !empty($configuration['wrappers'][$region_name]) ? $configuration['wrappers'][$region_name] : 'div',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['breakpoint'] = $form_state->getValue('breakpoint');
    $this->configuration['grid_container'] = $form_state->getValue('grid_container');
    $this->configuration['grid'] = $form_state->getValue('grid');
    $this->configuration['wrappers'] = $form_state->getValue('region_wrapper');
  }

}
