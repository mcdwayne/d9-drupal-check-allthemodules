<?php

namespace Drupal\social_auth_mastodon\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\social_auth\Form\SocialAuthSettingsForm;

/**
 * Settings form for Social Auth Mastodon.
 */
class MastodonAuthSettingsForm extends SocialAuthSettingsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_auth_mastodon_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array_merge(
      parent::getEditableConfigNames(),
      ['social_auth_mastodon.settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_auth_mastodon.settings');

    $form['mastodon_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Mastodon Client settings'),
      '#open' => TRUE,
      '#description' => $this->t('You need to first create a Mastodon App - for mastodon.social instance at <a href="@mastodon-social-dev">@mastodon-social-dev</a>',
        ['@mastodon-social-dev' => 'https://mastodon.social/settings/applications']
      ),
    ];

    $form['mastodon_settings']['instance'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Mastodon instance URI'),
      '#default_value' => $config->get('instance'),
      '#description' => $this->t('The Mastodon instance that hosts accounts you want to log in with'),
    ];

    $form['mastodon_settings']['client_id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Client ID (Client Key)'),
      '#default_value' => $config->get('client_id'),
      '#description' => $this->t('Copy the Client ID here.'),
    ];

    $form['mastodon_settings']['client_secret'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Client Secret'),
      '#default_value' => $config->get('client_secret'),
      '#description' => $this->t('Copy the Client Secret here.'),
    ];

    $form['mastodon_settings']['redirect_uri'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('Redirect URI'),
      '#description' => $this->t('Copy this to <em>Redirect URIs</em> when creating a key'),
      '#default_value' => Url::fromRoute('social_auth_mastodon.callback')->setAbsolute()->toString(),
    ];

    $form['mastodon_settings']['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => FALSE,
    ];

    $form['mastodon_settings']['advanced']['scopes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Scopes for API call'),
      '#default_value' => $config->get('scopes'),
      '#description' => $this->t('Define any additional scopes to be requested, separated by a comma (e.g.: follow,write:statuses,read:follows).<br>
                                  The scope \'read:accounts\' is added by default and always requested.<br>
                                  You can see the full list of valid scopes and their description <a href="@scopes">here</a>.', ['@scopes' => 'https://docs.joinmastodon.org/api/permissions/']),
    ];

    $form['mastodon_settings']['advanced']['endpoints'] = [
      '#type' => 'textarea',
      '#title' => $this->t('API calls to be made to collect data'),
      '#default_value' => $config->get('endpoints'),
      '#description' => $this->t('Define the Endpoints to be requested when user authenticates with Mastodon for the first time<br>
                                  Enter each endpoint in different lines in the format <em>endpoint</em>|<em>name_of_endpoint</em>.<br>
                                  <b>For instance:</b><br>
                                  /api/v1/accounts/relationships|relationships<br>
                                  Look for the endpoints in the <a href="@api-docs">Mastodon REST API documentation</a><br>', ['@api-docs' => 'https://docs.joinmastodon.org/api/rest/accounts/']),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('social_auth_mastodon.settings')
      ->set('instance', trim($values['instance']))
      ->set('client_id', trim($values['client_id']))
      ->set('client_secret', trim($values['client_secret']))
      ->set('scopes', trim($values['scopes']))
      ->set('endpoints', trim($values['endpoints']))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
