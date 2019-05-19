<?php

 /**
  * @file
  * Contains \Drupal\testswarm\Form\TestswarmAdminSettingsForm.
  */

namespace Drupal\testswarm\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Defines the testswarm admin form.
 */
class TestswarmAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'testswarm_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = \Drupal::config('testswarm.settings');
    $form['browserstack'] = array(
      '#type' => 'details',
      '#title' => t('Browserstack'),
      '#description' => t('Browserstack settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['browserstack']['browserstack_username'] = array(
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#default_value' => $config->get('browserstack_username'),
      '#description' => t('Your browserstack username'),
    );
    $form['browserstack']['browserstack_password'] = array(
      '#type' => 'textfield',
      '#title' => t('Password'),
      '#default_value' => $config->get('browserstack_password'),
      '#description' => t('Your browserstack password'),
    );
    $form['browserstack']['browserstack_api_url'] = array(
      '#type' => 'textfield',
      '#title' => t('API URL'),
      '#default_value' => $config->get('browserstack_api_url'),
      '#description' => t('The browserstack url all requests are made to. From the browserstack documentation: "All requests are made to http://api.browserstack.com/VERSION"')
    );
    if (module_exists('xmlrpc')) {
      $form['remote_storage'] = array(
        '#type' => 'details',
        '#title' => t('Remote storage'),
        '#description' => t('Remote storage settings'),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      );
      $form['remote_storage']['save_results_remote'] = array(
        '#type' => 'checkbox',
        '#title' => t('Save a copy of the test results on a remote server'),
        '#default_value' => $config->get('save_results_remote'),
      );
      $form['remote_storage']['save_results_remote_url'] = array(
        '#type' => 'textfield',
        '#title' => t('Endpoint URL'),
        '#description' => t('An absolute URL of the XML-RPC endpoint.'),
        '#default_value' => $config->get('save_results_remote_url'),
        '#states' => array(
          'visible' => array(
            ':input[name="save_results_remote"]' => array('checked' => TRUE),
          ),
          'required' => array(
            ':input[name="save_results_remote"]' => array('checked' => TRUE),
          ),
        )
      );
      $form['remote_storage']['generate_secret'] = array(
        '#type' => 'radios',
        '#title' => t('Create a new shared key'),
        '#options' => array(
          0 => t('Manually'),
          1 => t('Generate new key'),
        ),
        '#default_value' => 0,
      );
      $form['remote_storage']['shared_secret'] = array(
        '#type' => 'textfield',
        '#title' => t('Shared key'),
        '#description' => t('This key is used to counteract malicious calls to the remote server. It needs to be the same on the client and the server.'),
        '#default_value' => $config->get('shared_secret'),
        '#states' => array(
          'visible' => array(
            ':input[name="generate_secret"]' => array('value' => 0),
          ),
        ),
      );


    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc
   */
  public function validateForm(array &$form, array &$form_state) {
    $values = $form_state['values'];
    if (isset($values['testswarm_save_results_remote']) && $values['testswarm_save_results_remote']) {
      if (empty($values['testswarm_save_results_remote_url'])) {
        form_set_error('testswarm_save_results_remote_url', t('Endpoint URL is required.'));
      }
      if (!valid_url($values['testswarm_save_results_remote_url'], TRUE)) {
        form_set_error('testswarm_save_results_remote_url', t('Endpoint URL must be a valid URL.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $values = $form_state['values'];
    $config = \Drupal::config('testswarm.settings');
    $config
      ->set('browserstack_username', $values['browserstack_username'])
      ->set('browserstack_password', $values['browserstack_password'])
      ->set('browserstack_api_url', $values['browserstack_api_url']);

    if (module_exists('xmlrpc')) {
      $config
        ->set('shared_secret', $values['shared_secret'])
        ->set('save_results_remote', $values['save_results_remote'])
        ->set('save_results_remote_url', $values['save_results_remote_url']);

      if (isset($values['generate_secret']) && $values['generate_secret']) {
        $config->set('shared_secret', drupal_hash_base64(drupal_random_bytes(55)));
      }
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
