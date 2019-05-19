<?php

namespace Drupal\social_auth_pbs\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\social_auth\Form\SocialAuthSettingsForm;

/**
 * Settings form for Social Auth PBS.
 */
class PbsAuthSettingsForm extends SocialAuthSettingsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_auth_pbs_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array_merge(
      parent::getEditableConfigNames(),
      ['social_auth_pbs.settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_auth_pbs.settings');

    $form['pbs_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('PBS Client settings'),
      '#open' => TRUE,
      '#description' => $this->t('Submit a ticket to PBS Digital Support to
        obtain client settings.'),
    ];

    $form['pbs_settings']['client_id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Client ID'),
      '#default_value' => $config->get('client_id'),
      '#description' => $this->t('Copy the Client ID here.'),
    ];

    $form['pbs_settings']['client_secret'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Client Secret'),
      '#default_value' => $config->get('client_secret'),
      '#description' => $this->t('Copy the Client Secret here.'),
    ];

    $form['pbs_settings']['scopes'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Scopes'),
      '#default_value' => $config->get('scopes'),
      '#description' => $this->t('PBS Digital support should provide the
        necessary <em>Scopes</em> settings.'),
    ];

    $form['pbs_redirect_url'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Application Callback URL'),
      '#description' => $this->t('Submit a ticket to PBS Digital Support
        requesting the following <em>Application Callback URL</em> be added for
        the provided <em>Client ID</em>.'),
      '#default_value' => $GLOBALS['base_url'] . '/user/login/pbs/callback',
    ];

    $form['pbs_redirect_url']['url'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('<strong>Callback URL</strong><br/><code>@url</code>', [
        '@url' => Url::fromRoute('social_auth_pbs.callback')->setAbsolute()->toString(),
      ]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('social_auth_pbs.settings')
      ->set('client_id', $values['client_id'])
      ->set('client_secret', $values['client_secret'])
      ->set('scopes', $values['scopes'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
