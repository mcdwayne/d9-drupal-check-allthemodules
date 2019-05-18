<?php

/**
 * @file
 * Contains \Drupal\breakpoint_js_settings\Form\SettingsForm.
 */

namespace Drupal\breakpoint_js_settings\Form;

use Drupal\breakpoint\BreakpointManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures ivw settings.
 */
class SettingsForm extends ConfigFormBase {
  /**
   * The token object.
   *
   * @var BreakpointManagerInterface
   */
  protected $breakpointManager = array();

  /**
   * Constructs a \Drupal\breakpoint_js_settings\SettingsForm object.
   *
   * @param ConfigFactoryInterface $config_factory
   *  The factory for configuration objects.
   * @param BreakpointManagerInterface $breakpoint_manager
   *  The token object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    BreakpointManagerInterface $breakpoint_manager
  ) {
    parent::__construct($config_factory);
    $this->breakpointManager = $breakpoint_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'), $container->get('breakpoint.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'breakpoint_js_settings_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $definitions = $this->config('breakpoint_js_settings.settings');

    // get re-indexed default values
    $breakpoint_default = array_column($definitions->get('breakpoints'), NULL, 'breakpoint_id');
    $device_mapping_default = array_column($definitions->get('device_mappings'), 'breakpoint_id', 'device');

    $form['min_width'] = array(
      '#type' => 'details',
      '#title' => t('Min-widths for breakpoints'),
      '#open' => TRUE,
      '#description' => t('Define min-width for given breakpoint, keep empty if you do not wish to use this breakpoint. The breakpoints are extracted from all definitions found in corresponding *.breakpoint.yml files')
    );
    $form['device_mapping'] = array(
      '#type' => 'details',
      '#title' => t('Map devices to breakpoint'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#description' => t('map device types to the breakpoints')
    );

    foreach ($this->breakpointManager->getDefinitions() as $name => $definition) {
      $element_name = str_replace('.', '_', $name);
      $default_width = !empty($breakpoint_default[$name]['breakpoint_min_width']) ? $breakpoint_default[$name]['breakpoint_min_width'] : 0;
      $default_name = !empty($breakpoint_default[$name]['breakpoint_name']) ? $breakpoint_default[$name]['breakpoint_name'] : '';

      $form['min_width'][$element_name] = [
        '#type' => 'fieldgroup',
        '#title' => $name,
        '#tree' => TRUE,
        '#description' => t('Media query for @name is "@query"', array(
          '@name' => $name,
          '@query' => $definition['mediaQuery']
        )
        )
      ];
      $form['min_width'][$element_name]['id'] = [
        '#type' => 'hidden',
        '#value' => $name,
      ];
      $form['min_width'][$element_name]['name'] = [
        '#type' => 'textfield',
        '#title' => 'Name',
        '#size' => 10,
        '#default_value' => $default_name,
      ];
      $form['min_width'][$element_name]['min_width'] = [
        '#type' => 'textfield',
        '#title' => 'Min-width',
        '#size' => 10,
        '#field_suffix' => 'px',
        '#default_value' => $default_width,
      ];
    }

    $devices = ['smartphone', 'tablet', 'desktop'];

    foreach (array_keys($this->breakpointManager->getDefinitions()) as $definition) {
      $options[$definition] = $definition;
    }
    foreach ($devices as $device) {
      $default_mapping = $device_mapping_default[$device];

      $form['device_mapping'][$device]['breakpoint'] = [
        '#type' => 'select',
        '#title' => $device,
        '#options' => $options,
        '#empty_value' => '',
        '#default_value' => $default_mapping,
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $values = $form_state->getValues();

    $breakpoints = [];
    $mappings = [];
    foreach (Element::children($form['min_width']) as $width_child) {
      $width_child_values = $values[$width_child];
      if ($width_child_values['name'] !== '') {
        $breakpoints[] = [
          'breakpoint_id' => $width_child_values['id'],
          'breakpoint_name' => $width_child_values['name'],
          'breakpoint_min_width' => $width_child_values['min_width']
        ];
      }
    }

    foreach ($values['device_mapping'] as $device => $value) {
      $mappings[] = [
        'device' => $device,
        'breakpoint_id' => $value['breakpoint'],
      ];
    }

    $config = $this->configFactory()
      ->getEditable('breakpoint_js_settings.settings');
    $config->set('breakpoints', $breakpoints)
      ->set('device_mappings', $mappings)
      ->save();
  }


  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['breakpoint_js_settings.settings'];
  }
}
