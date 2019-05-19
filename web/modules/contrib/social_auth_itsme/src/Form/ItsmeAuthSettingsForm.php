<?php

namespace Drupal\social_auth_itsme\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\social_auth\Form\SocialAuthSettingsForm;
use Nascom\ItsmeApiClient\Request\Transaction\Scope;
use Nascom\ItsmeApiClient\Http\ApiClient\Service;

/**
 * Settings form for Social Auth itsme.
 */
class ItsmeAuthSettingsForm extends SocialAuthSettingsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_auth_itsme_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array_merge(
      parent::getEditableConfigNames(),
      ['social_auth_itsme.settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_auth_itsme.settings');

    $form['itsme_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('itsme Client settings'),
      '#open' => TRUE,
    ];

    $form['itsme_settings']['token'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Token'),
      '#default_value' => $config->get('token'),
      '#description' => $this->t('Copy the token here.'),
    ];

    $form['itsme_settings']['scopes'] = [
      '#type' => 'checkboxes',
      '#options' => [
        Scope::PROFILE => t('Profile'),
        Scope::EMAIL => t('Email'),
        Scope::PHONE => t('Phone'),
        Scope::ADDRESS => t('Address'),
      ],
      '#title' => $this->t('Scopes for API call'),
      '#default_value' => (is_array($config->get('scopes')) ? array_keys(array_filter($config->get('scopes'))) : []),
    ];

    $form['itsme_settings']['service'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#multiple' => 'true',
      '#options' => [
        Service::PRODUCTION => t('Production'),
        Service::SANDBOX => t('Sandbox'),
      ],
      '#title' => $this->t('Service to use to collect data'),
      '#default_value' => $config->get('service'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('social_auth_itsme.settings')
      ->set('token', $values['token'])
      ->set('scopes', $values['scopes'])
      ->set('service', $values['service'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
