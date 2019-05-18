<?php
/**
 * @file
 * Contains \Drupal\monitoring\Plugin\monitoring\SensorPlugin\EnabledModulesSensorPlugin.
 */

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorPlugin\SensorPluginBase;
use Drupal;
use Drupal\Core\Form\FormStateInterface;

/**
 * Monitors installed modules.
 *
 * @SensorPlugin(
 *   id = "monitoring_installed_modules",
 *   label = @Translation("Installed Modules"),
 *   description = @Translation("Monitors installed modules."),
 *   addable = FALSE
 * )
 *
 */
class EnabledModulesSensorPlugin extends SensorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Run the sensor to current setting and display the update button if
    // sensor result is critical.
    $configured_modules = array_filter($this->sensorConfig->getSetting('modules', NULL));

    // If the sensor is not configured, select installed modules.
    if (!$configured_modules) {
      $enabled_modules = Drupal::moduleHandler()->getModuleList();
      // Reduce to the module name only.
      $configured_modules = array_combine(array_keys($enabled_modules), array_keys($enabled_modules));
    }
    // Otherwise test run the sensor.
    else {
      // Run on a temporary sensor config id with some changes.
      /** @var \Drupal\monitoring\Entity\SensorConfig $run_config */
      $run_config = $this->sensorConfig->createDuplicate();
      // Avoid name clashes in SensorManager / caching.
      $run_config->id = $this->sensorConfig->id() . '_temp';
      // Force enabling the sensor for running.
      $run_config->status = TRUE;
      // Force no additional allowed to make differences visible in message.
      $run_config->settings['allow_additional'] = FALSE;

      /** @var \Drupal\monitoring\Result\SensorResult $result */
      $result = \Drupal::service('monitoring.sensor_runner')
        ->runSensors(array($run_config->id() => $run_config), TRUE)[$run_config->id()];

      if ($result->isCritical()) {
        $message = $result->getMessage();

        // Display message and button to update selection.
        $form['update_modules']['message'] = array(
          '#type' => 'item',
          '#title' => t('Test run message'),
          '#markup' => $message,
        );

        $form['update_modules']['update'] = array(
          '#type' => 'submit',
          '#value' => t('Update module selection'),
          '#limit_validation_errors' => array(),
          '#submit' => array(array($this, 'updateModuleListSubmit')),
          '#ajax' => array(
            'callback' => '::ajaxReplacePluginSpecificForm',
            'wrapper' => 'monitoring-sensor-plugin',
            'method' => 'replace',
          ),
        );
      }
    }

    $form['allow_additional'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow additional modules to be installed'),
      '#description' => t('If checked additionally installed modules will not be considered a critical state.'),
      '#default_value' => $this->sensorConfig->getSetting('allow_additional'),
    );

    // Get current list of available modules.
    // @todo find a faster solution? If that happens we can drop caching the
    //   result for 1 hour.
    $modules = system_rebuild_module_data();

    uasort($modules, 'system_sort_modules_by_info_name');

    $visible_modules = array();
    $visible_default_value = array();
    $hidden_modules = array();
    $hidden_default_value = array();

    foreach ($modules as $module => $module_data) {
      // Skip profiles.
      if (strpos(drupal_get_path('module', $module), 'profiles') === 0) {
        continue;
      }
      // As we also include hidden modules, some might have no name at all,
      // make sure it is set.
      if (!isset($module_data->info['name'])) {
        $module_data->info['name'] = '- No name -';
      }
      if (!empty($module_data->info['hidden'])) {
        $hidden_modules[$module] = $module_data->info['name'] . ' (' . $module . ')';
        if (!empty($configured_modules[$module])) {
          $hidden_default_value[$module] = $configured_modules[$module];
        }
      }
      else {
        $visible_modules[$module] = $module_data->info['name'] . ' (' . $module . ')';
        if (!empty($configured_modules[$module])) {
          $visible_default_value[$module] = $configured_modules[$module];
        }
      }
    }

    $form['modules'] = array(
      '#type' => 'checkboxes',
      '#options' => $visible_modules,
      '#title' => t('Modules expected to be installed'),
      '#description' => t('Check all modules that are supposed to be installed.'),
      '#default_value' => $visible_default_value,
    );

    $form['extended'] = array(
      '#type' => 'details',
      '#title' => 'Extended',
      '#open' => count($hidden_default_value) ? TRUE : FALSE,
    );

    $form['extended']['modules_hidden'] = array(
      '#type' => 'checkboxes',
      '#options' => $hidden_modules,
      '#title' => t('Hidden modules expected to be installed'),
      '#default_value' => $hidden_default_value,
      '#description' => t('Check all modules that are supposed to be installed.'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $sensor_config = $form_state->getFormObject()->getEntity();

    parent::submitConfigurationForm($form, $form_state);

    $modules = $form_state->getValue(array('settings', 'modules'));
    $hidden_modules = $form_state->getValue(array(
      'settings', 'extended', 'modules_hidden'));
    $modules = array_merge(array_filter($modules), array_filter($hidden_modules));
    $sensor_config->settings['modules'] = $modules;

    unset($sensor_config->settings['extended']);
    unset($sensor_config->settings['update_modules']);
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    // Load the info from the system table to display the label.
    $result->setExpectedValue(0);
    $delta = 0;

    $modules = system_rebuild_module_data();
    $names = array();
    foreach ($modules as $name => $module) {
      $names[$name] = $module->info['name'];
    }

    $monitoring_installed_modules = array();
    // Filter out install profile.
    foreach (array_keys(Drupal::moduleHandler()->getModuleList()) as $module) {
      $path_parts = explode('/', drupal_get_path('module', $module));
      if ($path_parts[0] != 'profiles') {
        $monitoring_installed_modules[$module] = $module;
      }
    }

    $expected_modules = array_filter($this->sensorConfig->getSetting('modules'));

    // If there are no expected modules, the sensor is not configured, so init
    // the expected modules list as currently installed modules.
    if (empty($expected_modules)) {
      $expected_modules = $monitoring_installed_modules;
      $this->sensorConfig->settings['modules'] = $monitoring_installed_modules;
      $this->sensorConfig->save();
    }

    // Check for modules not being installed but expected.
    $non_installed_modules = array_diff($expected_modules, $monitoring_installed_modules);
    if (!empty($non_installed_modules)) {
      $delta += count($non_installed_modules);
      $non_installed_modules_info = array();
      foreach ($non_installed_modules as $non_installed_module) {
        if (isset($names[$non_installed_module])) {
          $non_installed_modules_info[] = $names[$non_installed_module] . ' (' . $non_installed_module . ')';
        }
        else {
          $non_installed_modules_info[] = new FormattableMarkup('@module_name (unknown)', array('@module_name' => $non_installed_module));
        }
      }
      $result->addStatusMessage('Following modules are expected to be installed: @modules', array('@modules' => implode(', ', $non_installed_modules_info)));
    }

    // In case we do not allow additional modules check for modules installed
    // but not expected.
    $unexpected_modules = array_diff($monitoring_installed_modules, $expected_modules);
    if (!$this->sensorConfig->getSetting('allow_additional') && !empty($unexpected_modules)) {
      $delta += count($unexpected_modules);
      $unexpected_modules_info = array();
      foreach ($unexpected_modules as $unexpected_module) {
        $unexpected_modules_info[] = $names[$unexpected_module] . ' (' . $unexpected_module . ')';
      }
      $result->addStatusMessage('Following modules are NOT expected to be installed: @modules', array('@modules' => implode(', ', $unexpected_modules_info)));
    }

    $result->setValue($delta);
  }

  /**
   * Updates the module selection and override user input.
   */
  public function updateModuleListSubmit(array &$form, FormStateInterface $form_state) {

    // Get the installed module list.
    $enabled_modules = Drupal::moduleHandler()->getModuleList();

    // Reduce to the module name only.
    $default_value = array_combine(array_keys($enabled_modules), array_keys($enabled_modules));

    // Override the current input values to the default configuration.
    $user_input = $form_state->getUserInput();
    $user_input['settings']['modules'] = $default_value;
    $form_state->setUserInput($user_input);
    $entity = $form_state->getFormObject()->getEntity();
    $entity->settings['modules'] = $default_value;
    $form_state->setRebuild(TRUE);

    drupal_set_message(t('Module list updateed, Save to confirm.'));
  }
}
