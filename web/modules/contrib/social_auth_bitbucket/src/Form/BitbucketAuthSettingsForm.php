<?php

namespace Drupal\social_auth_bitbucket\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\social_auth\Form\SocialAuthSettingsForm;

/**
 * Settings form for Social Auth Bitbucket.
 */
class BitbucketAuthSettingsForm extends SocialAuthSettingsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_auth_bitbucket_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array_merge(
      parent::getEditableConfigNames(),
      ['social_auth_bitbucket.settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_auth_bitbucket.settings');

    $form['bitbucket_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Bitbucket Client settings'),
      '#open' => TRUE,
      '#description' => $this->t('You need to first configure your Bitbucket settings.
                                 Check <a href="@bitbucket-dev">Bitbucket Documentation</a> for more information.<br>
                                 Create a new app at <em>https://bitbucket.org/account/user/{your-user-name}/oauth-consumers/new</em>',
                                 ['@bitbucket-dev' => 'https://confluence.atlassian.com/bitbucket/oauth-on-bitbucket-cloud-238027431.html']),
    ];

    $form['bitbucket_settings']['key'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Key'),
      '#default_value' => $config->get('key'),
      '#description' => $this->t('Copy the Key here.'),
    ];

    $form['bitbucket_settings']['secret'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Secret'),
      '#default_value' => $config->get('secret'),
      '#description' => $this->t('Copy the Secret here.'),
    ];

    $form['bitbucket_settings']['authorized_redirect_url'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('Authorized redirect URIs'),
      '#description' => $this->t('Copy this value to <em>Authorized redirect URIs</em> field of your Bitbucket App settings.'),
      '#default_value' => Url::fromRoute('social_auth_bitbucket.callback')->setAbsolute()->toString(),
    ];

    $form['bitbucket_settings']['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => FALSE,
    ];

    $form['bitbucket_settings']['advanced']['scopes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Scopes for API call'),
      '#default_value' => $config->get('scopes'),
      '#description' => $this->t('Define any additional scopes to be requested, separated by a comma (e.g.: team,repository).<br>
                                  The scopes \'account\' and \'email\' are added by default and always requested.<br>
                                  You can see the full list of valid fields and required scopes <a href="@fields">here</a>.', ['@fields' => 'https://developer.atlassian.com/bitbucket/api/2/reference/resource/']),
    ];

    $form['bitbucket_settings']['advanced']['endpoints'] = [
      '#type' => 'textarea',
      '#title' => $this->t('API calls to be made to collect data'),
      '#default_value' => $config->get('endpoints'),
      '#description' => $this->t('Define the endpoints to be requested when user authenticates with Bitbucket for the first time<br>
                                  Enter each endpoint in different lines in the format <em>endpoint</em>|<em>name_of_endpoint</em>.<br>
                                  <b>For instance:</b><br>
                                  /2.0/teams?role=member|user_teams'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('social_auth_bitbucket.settings')
      ->set('key', $values['key'])
      ->set('secret', $values['secret'])
      ->set('scopes', $values['scopes'])
      ->set('endpoints', $values['endpoints'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
