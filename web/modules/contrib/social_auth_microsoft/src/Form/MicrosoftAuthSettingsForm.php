<?php

namespace Drupal\social_auth_microsoft\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\social_auth\Form\SocialAuthSettingsForm;

/**
 * Settings form for Social Auth Microsoft.
 */
class MicrosoftAuthSettingsForm extends SocialAuthSettingsForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_auth_microsoft_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array_merge(
      parent::getEditableConfigNames(),
      ['social_auth_microsoft.settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_auth_microsoft.settings');

    $form['microsoft_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Microsoft Client settings'),
      '#open' => TRUE,
      '#description' => $this->t('You need to first create a Microsoft App at <a href="@microsoft-dev">@microsoft-dev</a>',
        ['@microsoft-dev' => 'https://apps.dev.microsoft.com/?mkt=en-us#/appList']),
    ];

    $form['microsoft_settings']['app_id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Application ID'),
      '#default_value' => $config->get('app_id'),
      '#description' => $this->t('Copy the App ID here.'),
    ];

    $form['microsoft_settings']['app_secret'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Application Secret'),
      '#default_value' => $config->get('app_secret'),
      '#description' => $this->t('Copy the App Secret here.'),
    ];

    $form['microsoft_settings']['authorized_redirect_url'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('Authorized redirect URIs'),
      '#description' => $this->t('Copy this value to <em>Authorized redirect URIs</em> field of your Microsoft App settings.'),
      '#default_value' => Url::fromRoute('social_auth_microsoft.callback')->setAbsolute()->toString(),
    ];

    /*
     * @todo: Uncomment this.
     * @see https://github.com/stevenmaguire/oauth2-microsoft/issues/12
     */

    /*
    $form['microsoft_settings']['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => FALSE,
    ];

    $form['microsoft_settings']['advanced']['scopes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Scopes for API call'),
      '#default_value' => $config->get('scopes'),
      '#description' => $this->t('Define any additional scopes to be requested, separated by a comma (e.g.: Contacts.Read,Files.Read).<br>
                                  The scopes \'wl.basic\', \'wl.signin\', and \'wl.emails\' are added by default and always requested.<br>
                                  You can see the full list of valid fields and required scopes <a href="@fields">here</a>.', ['@fields' => 'https://developer.microsoft.com/en-us/graph/docs/concepts/overview']),
    ];

    $form['microsoft_settings']['advanced']['endpoints'] = [
      '#type' => 'textarea',
      '#title' => $this->t('API calls to be made to collect data'),
      '#default_value' => $config->get('endpoints'),
      '#description' => $this->t('Define the Endpoints to be requested when user authenticates with Facebook for the first time<br>
                                  Enter each endpoint in different lines in the format <em>endpoint</em>|<em>name_of_endpoint</em>.<br>
                                  <b>For instance:</b><br>
                                  /v1.0/me/drives|drives'),

    ];
    */

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('social_auth_microsoft.settings')
      ->set('app_id', $values['app_id'])
      ->set('app_secret', $values['app_secret'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
