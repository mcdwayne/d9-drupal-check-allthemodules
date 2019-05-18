<?php

/**
 * @file
 * Contains \Drupal\maestro\MaestroEngineSettingsForm
 */

namespace Drupal\maestro\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure settings for this site.
 */
class MaestroEngineSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'maestro_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'maestro.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('maestro.settings');

    $default = $config->get('maestro_redirect_location');
    $form['maestro_redirect_location'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('URI used in notifications to redirect the recipient to.'),
      '#default_value' => isset($default) ? $default : '/taskconsole',
      '#description' => $this->t('Defaults to /taskconsole'),
      '#required' => TRUE,
    );

    $form['maestro_send_notifications'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t("Send out notifications"),
      '#default_value' => $config->get('maestro_send_notifications'),
      '#description' => $this->t('When checked, this config value will enable outgoing notifications. '),
    );
    
    $form['maestro_orchestrator_task_console'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t("Run the Orchestrator on Task Console Refreshes"),
      '#default_value' => $config->get('maestro_orchestrator_task_console'),
      '#description' => $this->t('When checked, a refresh of the Task Console (provided by Maestro) will run the orchestrator. '),
    );

    $default = $config->get('maestro_orchestrator_token');
    $form['maestro_orchestrator_token'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('The token that MUST be appended to the /orchestrator URL in order to run the orchestrator.'),
        '#default_value' => isset($default) ? $default : '',
        '#description' => $this->t('Defaults to nothing.  YOU MUST SET THIS!  Resulting URL to run the orchestrator is http://[site]/orchestrator/{token}'),
        '#required' => TRUE,
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('maestro.settings')
      ->set('maestro_send_notifications', $form_state->getValue('maestro_send_notifications'))
      ->save();
    
    $this->config('maestro.settings')
      ->set('maestro_orchestrator_task_console', $form_state->getValue('maestro_orchestrator_task_console'))
      ->save();  
    
    $this->config('maestro.settings')
      ->set('maestro_redirect_location', $form_state->getValue('maestro_redirect_location'))
      ->save();
    
    $this->config('maestro.settings')
      ->set('maestro_orchestrator_token', $form_state->getValue('maestro_orchestrator_token'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
