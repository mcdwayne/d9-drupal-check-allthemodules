<?php

namespace Drupal\responsive_class_field;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\breakpoint\BreakpointManagerInterface;

/**
 * Responsive class field service.
 *
 * Provides helper methods for the module settings.
 */
class ResponsiveClassField {

  use StringTranslationTrait;

  /**
   * The responsive_class_field module configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface
   */
  protected $conditionPluginManager;

  /**
   * The CSS attach conditions collection.
   *
   * @var \Drupal\Core\Condition\ConditionInterface[]
   */
  protected $conditions;

  /**
   * The breakpoint manager service.
   *
   * @var \Drupal\breakpoint\BreakpointManagerInterface
   */
  protected $breakpointManager;

  /**
   * Construct a ResponsiveClassField service instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $condition_plugin_manager
   *   The factory for condition plugins.
   * @param \Drupal\breakpoint\BreakpointManagerInterface $breakpoint_manager
   *   The breakpoint manager service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ExecutableManagerInterface $condition_plugin_manager,
    BreakpointManagerInterface $breakpoint_manager
  ) {
    $this->config = $config_factory->get('responsive_class_field.settings');
    $this->conditionPluginManager = $condition_plugin_manager;
    $this->breakpointManager = $breakpoint_manager;
  }

  /**
   * Return the condition plugin manager.
   *
   * @return \Drupal\Core\Executable\ExecutableManagerInterface
   *   The condition plugin manager.
   */
  public function getConditionPluginManager() {
    return $this->conditionPluginManager;
  }

  /**
   * Return all attach condition plugins.
   *
   * @return \Drupal\Core\Condition\ConditionInterface[]
   *   Array of attach condition plugins keyed by instance ID.
   */
  public function getConditions() {
    if (!isset($this->conditions)) {
      $defaults = [
        'current_theme' => [],
      ];

      $settings = $this->config->get('attach_conditions');
      $settings = empty($settings) ? $defaults : array_merge_recursive($defaults, $settings);

      $condition_plugins = [];
      foreach ($settings as $instance_id => $config) {
        $condition_plugins[$instance_id] = $this->conditionPluginManager->createInstance($instance_id, $config);
      }

      $this->conditions = $condition_plugins;
    }
    return $this->conditions;
  }

  /**
   * Return a specific attach condition plugin instance by instance ID.
   *
   * @param string $instance_id
   *   The condition plugin instance ID.
   *
   * @return \Drupal\Core\Condition\ConditionInterface
   *   An attach condition plugin instance.
   */
  public function getCondition($instance_id) {
    return $this->getConditions()[$instance_id];
  }

  /**
   * Check, whether the CSS attach conditions apply.
   *
   * @return bool
   *   TRUE, if the CSS attach conditions apply, FALSE
   *   otherwise.
   */
  public function checkConditions() {
    $conditions = $this->getConditions();

    // Whether any of the configured condition plugins does not
    // apply to the current context.
    foreach ($conditions as $condition) {
      if (!$condition->execute()) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Return the configured default breakpoint group.
   *
   * @return string
   *   Configured default breakpoint group.
   */
  public function getDefaultBreakpointGroup() {
    return $this->config->get('breakpoint_defaults.breakpoint_group') ?: '';
  }

  /**
   * Return an associative array of the configured default breakpoint settings.
   *
   * @return array
   *   Associative array of the configured default breakpoint settings keyed by
   *   breakpoint IDs.
   */
  public function getDefaultBreakpoints() {
    return self::breakpointSettingsFromConfig($this->config->get('breakpoint_defaults.breakpoints') ?: []);
  }

  /**
   * Return all existing breakpoint groups.
   *
   * This is a wrapper for
   * \Drupal\breakpoint\BreakpointManagerInterface::getGroups(), so our
   * forms don't have to separately use this service to gain breakpoint
   * information.
   *
   * @return array
   *   Array of breakpoint group labels. Keyed by group name.
   */
  public function getBreakpointGroups() {
    return $this->breakpointManager->getGroups();
  }

  /**
   * Return the breakpoints settings subform.
   *
   * @param array $form
   *   The initial form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param string $breakpoints_group
   *   The breakpoints group identifier of the breakpoints to
   *   configure.
   * @param array|null $default_values
   *   (Optional) Default values to use for the form. If NULL is
   *   given, the configured responsive class field breakpoints
   *   settings will be used as default values.
   * @param string $name_prefix
   *   (Optional) Prefix for form element names. This form uses
   *   field name specific visibility states. If it is used as
   *   subform, you need to provide the names of its parent
   *   elements with this parameter.
   *   Defaults to an empty string.
   *
   * @return array
   *   The form render array for the breakpoints settings form.
   */
  public function buildBreakpointsSettingsForm(array $form, FormStateInterface $form_state, $breakpoints_group, $default_values = NULL, $name_prefix = '') {
    $breakpoints = $this->breakpointManager->getBreakpointsByGroup($breakpoints_group);
    if (is_null($default_values)) {
      $default_values = $this->getDefaultBreakpoints();
    }
    $name_prefix = empty($name_prefix) ? 'breakpoints' : $name_prefix . '[breakpoints]';

    $form['breakpoints'] = [
      '#type' => 'container',
      '#tree' => TRUE,
      '#attributes' => [
        'id' => 'responsive-class-breakpoints-wrapper',
      ],
    ];

    foreach ($breakpoints as $breakpoint_id => $breakpoint) {
      $form['breakpoints'][$breakpoint_id] = [
        '#type' => 'details',
        '#title' => $breakpoint->getLabel(),
        '#open' => !empty($default_values[$breakpoint_id]['enabled']),
      ];
      $form['breakpoints'][$breakpoint_id]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use this breakpoint'),
        '#default_value' => !empty($default_values[$breakpoint_id]['enabled']),
      ];
      $form['breakpoints'][$breakpoint_id]['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Breakpoint label'),
        '#description' => $this->t('The label of this breakpoint that will be used in edit forms.'),
        '#default_value' => !empty($default_values[$breakpoint_id]['label']) ? $default_values[$breakpoint_id]['label'] : $breakpoint->getLabel(),
        '#states' => [
          'visible' => [
            ':input[name="' . $name_prefix . '[' . $breakpoint_id . '][enabled]"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['breakpoints'][$breakpoint_id]['token'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Breakpoint value'),
        '#description' => $this->t('The text that will replace the <em>{breakpoint}</em> token of the class pattern, if a value has been chosen for this breakpoint.'),
        '#default_value' => !empty($default_values[$breakpoint_id]['token']) ? $default_values[$breakpoint_id]['token'] : '',
        '#states' => [
          'visible' => [
            ':input[name="' . $name_prefix . '[' . $breakpoint_id . '][enabled]"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    return $form;
  }

  /**
   * Prepare breakpoint settings for storage in configuration.
   *
   * Breakpoint identifiers may have dots within their IDs. As we store
   * breakpoint settings as sequence and sequence keys shouldn't have dots,
   * we transform associative arrays to simple arrays and add the
   * breakpoint IDs to the values.
   *
   * @param array $breakpoints
   *   Associative array of breakpoints settings.
   * @param bool $onlyEnabled
   *   (Optional) Whether to clean the values array from disabled
   *   breakpoints and remove the 'enabled' key.
   *   Defaults to TRUE.
   *
   * @return array
   *   Simple array with breakpoint IDs added to the values.
   */
  public static function breakpointSettingsToConfig(array $breakpoints, $onlyEnabled = TRUE) {
    $simple = [];
    foreach ($breakpoints as $key => $value) {
      if ($onlyEnabled && empty($value['enabled'])) {
        continue;
      }

      $breakpoint = $value;
      if ($onlyEnabled) {
        unset($breakpoint['enabled']);
      }
      $breakpoint['breakpoint'] = $key;

      $simple[] = $breakpoint;
    }
    return $simple;
  }

  /**
   * Optimize breakpoint settings for for use in our module.
   *
   * This is the counter part of self::breakpointSettingsToConfig(). It
   * expands a simple array containing breakpoint IDs to an associative
   * array keyed by these IDs.
   *
   * @param array $breakpoints
   *   Array of breakpoints settings as returned from configuration sequences.
   * @param bool $addEnabled
   *   (Optional) Whether to add an 'enabled'/TRUE key/value pair to the
   *   resulting settings.
   *   Defaults to TRUE.
   *
   * @return array
   *   Associative breakpoints settings array keyed by breakpoint IDs.
   */
  public static function breakpointSettingsFromConfig(array $breakpoints, $addEnabled = TRUE) {
    $expanded = [];

    foreach ($breakpoints as $value) {
      $breakpoint = $value;
      unset($breakpoint['breakpoint']);
      if ($addEnabled) {
        $breakpoint['enabled'] = TRUE;
      }

      $expanded[$value['breakpoint']] = $breakpoint;
    }

    return $expanded;
  }

}
