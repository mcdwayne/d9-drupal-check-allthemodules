<?php

/**
 * @file
 * Contains \Drupal\pilot\Form\PilotConfigForm.
 */
namespace Drupal\pilot\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class PilotConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pilot_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('pilot.settings');

    $form['api_token'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('API Token'),
      '#default_value' => $config->get('pilot.token'),
      '#required' => TRUE,
    );

    pilot_module_list();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('pilot.settings');

    $config->set('pilot.token', $form_state->getValue('api_token'));
    $config->save();

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'pilot.settings',
    ];
  }
}
