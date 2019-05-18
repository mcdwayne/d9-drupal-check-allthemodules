<?php

/**
 * @file
 * Contains \Drupal\habitat_ui\Form\SettingsForm.
 */

namespace Drupal\habitat_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures habitat settings.
 */
class SettingsForm extends ConfigFormBase {
  /**
   * An array of configuration names that should be editable.
   *
   * @var array
   */
  protected $editableConfig = [];
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'habitat_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return $this->editableConfig;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('habitat.settings');
    $habitats = $config->get('habitat_habitats');

    $form['variable'] = array(
      '#type' => 'textfield',
      '#title' => t('Habitat variable name'),
      '#required' => TRUE,
      '#default_value' => $config->get('habitat_variable'),
      '#description' => t('The habitat variable used in your settings.php files to indicate the habitat. This should be placed into settings.php like $settings[\'!variable\'] = \'dev\'. Defaults to \'fetcher_environment\' which is added to settings.php when sites are built with the !fetcher system.', array('!variable' => $config->get('habitat_variable'), '!fetcher' => '<a href="http://drupal.org/project/fetcher">fetcher</a>')),
    );

    $form['habitats'] = array('#type' => 'textarea',
      '#title' => t('Habitats'),
      '#description' => t('The habitats to manage. Use machine_name conventions and enter one per line.'),
       '#required' => TRUE,
       '#default_value' => implode("\n", $habitats),
    );
    foreach ($habitats as $habitat) {
      $form['install_' . $habitat] = array(
        '#type' => 'textarea',
        '#title' => t('%habitat installed modules', array('%habitat' => $habitat)),
        '#description' => t('The modules to force install in this habitat. Use machine_name conventions and enter one per line.'),
        '#default_value' => implode("\n", $config->get('habitat_install_' . $habitat)),
      );
      $form['uninstall_' . $habitat] = array(
        '#type' => 'textarea',
        '#title' => t('%habitat uninstalled modules', array('%habitat' => $habitat)),
        '#description' => t('The modules to force uninstall in this habitat. Use machine_name conventions and enter one per line.'),
        '#default_value' => implode("\n", $config->get('habitat_uninstall_' . $habitat)),
      );
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('habitat.settings');

    $config->set('habitat_variable', $form_state->getValue('variable'));

    $habitats = array_filter(array_map('trim', explode("\n", $form_state->getValue('habitats'))));
    $config->set('habitat_habitats', $habitats);

    $original_habitats = $config->get('habitat_habitats');
    foreach ($original_habitats as $original_habitat) {
      if (in_array($original_habitat, $habitats)) {
        $config->set('habitat_install_' . $original_habitat, array_filter(array_map('trim', explode("\n", $form_state->getValue('install_' . $original_habitat)))));
        $config->set('habitat_uninstall_' . $original_habitat, array_filter(array_map('trim', explode("\n", $form_state->getValue('uninstall_' . $original_habitat)))));
      }
      else {
        $config->clear('habitat_install_' . $original_habitat);
        $config->clear('habitat_uninstall_' . $original_habitat);
      }
    }

    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('habitat.settings');
    $habitats = $config->get('habitat_habitats');
    $module_data = system_rebuild_module_data();

    foreach ($habitats as $habitat) {
      foreach (array('install', 'uninstall') as $type) {
        $modules = array_filter(array_map('trim', explode("\n", $form_state->getValue($type . '_' . $habitat))));
        $module_list = $modules ? array_combine($modules, $modules) : array();
        if ($missing_modules = array_diff_key($module_list, $module_data)) {
          $form_state->setErrorByName($type . '_' . $habitat, t('Cannot set @habitat @typeed modules due to missing modules @modules.', array('@habitat' => $habitat, '@type' => $type, '@modules' => implode(', ', $missing_modules))));
        }
      }
    }
  }
}

