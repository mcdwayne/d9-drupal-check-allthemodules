<?php

namespace Drupal\http_auth\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provide settings page for applying Http Auth on your site.
 */
class HttpAuthSettings extends ConfigFormBase {

  /**
   * Implements FormBuilder::getFormId.
   */
  public function getFormId() {
    return 'http_auth';
  }

  /**
   * Implements ConfigFormBase::getEditableConfigNames.
   */
  protected function getEditableConfigNames() {
    return ['http_auth.settings'];
  }

  /**
   * Implements FormBuilder::buildForm.
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $http_auth_section = $this->config('http_auth.settings')->get();
    $applicable = [
      'complete' => $this->t('Complete Site'),
      'admin'    => $this->t('Admin and User Pages'),
    ];

    $form['http_auth'] = [
      '#type'        => 'fieldset',
      '#title'       => $this->t('Add HTTP Auth on your site'),
      '#description' => $this->t('By activating, your site or admin pages would be <strong>locked</strong> for unauthenticated users.'),
    ];

    $form['http_auth']['username'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('HTTP Auth Username'),
      '#description'   => $this->t('Add HTTP Auth username'),
      '#default_value' => isset($http_auth_section['username']) ? $http_auth_section['username'] : '',
      '#size'          => 60,
      '#maxlength'     => 64,
      '#required'      => TRUE,
      '#attributes'    => [
        'placeholder'  => 'username',
      ],
    ];

    $form['http_auth']['password'] = [
      '#type'          => 'password',
      '#title'         => $this->t('HTTP Auth password'),
      '#description'   => $this->t('Add HTTP Auth password'),
      '#size'          => 60,
      '#maxlength'     => 64,
      '#required'      => TRUE,
      '#attributes'    => [
        'placeholder'  => 'password',
      ],
    ];

    $form['http_auth']['message'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('HTTP Auth Message'),
      '#description'   => $this->t('Add HTTP Auth message which would be shown to the unauthenticated users.'),
      '#default_value' => isset($http_auth_section['message']) ? $http_auth_section['message'] : '',
      '#attributes'    => [
        'placeholder'  => $this->t('This page is Restricted. Please contact the administrator for access.'),
      ],
    ];

    $form['http_auth']['applicable'] = [
      '#type'          => 'radios',
      '#title'         => $this->t('Applicable on:'),
      '#default_value' => isset($http_auth_section['applicable']) ? $http_auth_section['applicable'] : 'complete',
      '#options'       => $applicable,
    ];

    $form['http_auth']['activate'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Activate HTTP Authentication'),
      '#default_value' => isset($http_auth_section['activate']) ? $http_auth_section['activate'] : 0,
    ];

    $form['http_auth']['note'] = [
      '#markup' => "<div><strong>Note:</strong> Please clear the cache if the settings wouldn't work!</div>",
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements FormBuilder::submitForm().
   *
   * Save the HTTP Auth Details to to the Drupal's config Table.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->configFactory()
      ->getEditable('http_auth.settings')
      ->set('username', $values['username'])
      ->set('password', $values['password'])
      ->set('message', $values['message'])
      ->set('applicable', $values['applicable'])
      ->set('activate', $values['activate'])
      ->save();

    drupal_set_message($this->t('Your Settings have been saved.'), 'status');
  }

}
