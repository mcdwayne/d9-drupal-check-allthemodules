<?php

namespace Drupal\required_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure default required stratety for this site.
 */
class RequiredDefaultPluginForm extends ConfigFormBase {

  /**
   * Required method to provide the form_id.
   */
  public function getFormId() {
    return 'required_default_plugin';
  }

  /**
   * Required method to provide the actual form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $manager = \Drupal::service('plugin.manager.required_api.required');
    $plugins = $manager->getDefinitionsAsOptions();

    $config = $this->configFactory->get('required_api.plugins');
    $plugin = $config->get('default_plugin');

    $form['default_plugin'] = array(
      '#title' => t('Default required strategy'),
      '#type' => 'radios',
      '#options' => $plugins,
      '#default_value' => $plugin,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit function for the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('required_api.plugins');

    $config->set('default_plugin', $form_state->getValue('default_plugin'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  protected function getEditableConfigNames() {
    return [
      'required_api.plugins',
    ];
  }
}
