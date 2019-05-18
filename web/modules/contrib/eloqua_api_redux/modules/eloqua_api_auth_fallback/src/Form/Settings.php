<?php

namespace Drupal\eloqua_api_auth_fallback\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Eloqua API Fallback Authentication Settings.
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eloqua_api_auth_fallback_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['eloqua_api_auth_fallback.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('eloqua_api_auth_fallback.settings');

    $form['credentials'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Eloqua API resource owner password credentials'),
    ];

    $form['credentials']['help'] = [
      '#type' => '#markup',
      '#markup' => $this->t('For more information @link.',
        [
          '@link' => Link::fromTextAndUrl('refer to API Documentation for resource owner password credentials grant',
            Url::fromUri('https://docs.oracle.com/cloud/latest/marketingcs_gs/OMCAB/Developers/GettingStarted/Authentication/authenticate-using-oauth.htm#resource-owner-password-credentials-grant'))->toString(),
        ]),
    ];

    $form['credentials']['sitename'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Instance/Site Name'),
      '#description' => $this->t('Site name is the company name you use to log in to Eloqua'),
      '#default_value' => $config->get('sitename'),
    ];

    $form['credentials']['username'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Username'),
      '#description' => $this->t('Username you use to log in to Eloqua'),
      '#default_value' => $config->get('username'),
    ];

    $form['credentials']['password'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Password'),
      '#description' => $this->t('Password you use to log in to Eloqua'),
      '#default_value' => $config->get('password'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('eloqua_api_auth_fallback.settings')
      ->set('sitename', $form_state->getValue('sitename'))
      ->set('username', $form_state->getValue('username'))
      ->set('password', $form_state->getValue('password'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
