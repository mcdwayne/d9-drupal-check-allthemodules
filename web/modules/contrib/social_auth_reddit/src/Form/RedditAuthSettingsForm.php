<?php

namespace Drupal\social_auth_reddit\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\social_auth\Form\SocialAuthSettingsForm;

/**
 * Settings form for Social Auth Reddit.
 */
class RedditAuthSettingsForm extends SocialAuthSettingsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_auth_reddit_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array_merge(
      parent::getEditableConfigNames(),
      ['social_auth_reddit.settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_auth_reddit.settings');

    $form['reddit_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Reddit Client settings'),
      '#open' => TRUE,
      '#description' => $this->t('You need to first create a Reddit App at <a href="@reddit-dev">@reddit-dev</a> by clicking "are you a developer? create an app..."',
        ['@reddit-dev' => 'https://www.reddit.com/prefs/apps']),
    ];

    $form['reddit_settings']['client_id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Client ID'),
      '#default_value' => $config->get('client_id'),
      '#description' => $this->t('Copy the Client ID here.'),
    ];

    $form['reddit_settings']['client_secret'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Client Secret'),
      '#default_value' => $config->get('client_secret'),
      '#description' => $this->t('Copy the Client Secret here.'),
    ];

    $form['reddit_settings']['user_agent_string'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User Agent String'),
      '#default_value' => $config->get('user_agent_string'),
      '#required' => TRUE,
      '#description' => $this->t("Enter the user agent string to be used. The format is <em>platform:appid:version (by /u/username)</em>."),
    ];

    $form['reddit_settings']['authorized_redirect_url'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('Authorized redirect URIs'),
      '#description' => $this->t('Copy this value to <em>Authorized redirect URIs</em> field of your Reddit App settings.'),
      '#default_value' => Url::fromRoute('social_auth_reddit.callback')->setAbsolute()->toString(),
    ];

    $form['reddit_settings']['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => FALSE,
    ];

    $form['reddit_settings']['advanced']['scopes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Scopes for API call'),
      '#default_value' => $config->get('scopes'),
      '#description' => $this->t('Define any additional scopes to be requested, separated by a comma (e.g.: edit,history).<br>
                                  The scopes \'identity\' and \'read\' are added by default and always requested.<br>
                                  You can see the full list of valid endpoints and required scopes <a href="@scopes">here</a>.', ['@scopes' => 'https://www.reddit.com/dev/api/oauth']),
    ];

    $form['reddit_settings']['advanced']['endpoints'] = [
      '#type' => 'textarea',
      '#title' => $this->t('API calls to be made to collect data'),
      '#default_value' => $config->get('endpoints'),
      '#description' => $this->t('Define the Endpoints to be requested when user authenticates with Reddit for the first time<br>
                                  Enter each endpoint in different lines in the format <em>endpoint</em>|<em>name_of_endpoint</em>.<br>
                                  <b>For instance:</b><br>
                                  /api/v1/me/trophies|user_trophies'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('social_auth_reddit.settings')
      ->set('client_id', trim($values['client_id']))
      ->set('client_secret', trim($values['client_secret']))
      ->set('user_agent_string', $values['user_agent_string'])
      ->set('scopes', $values['scopes'])
      ->set('endpoints', $values['endpoints'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
