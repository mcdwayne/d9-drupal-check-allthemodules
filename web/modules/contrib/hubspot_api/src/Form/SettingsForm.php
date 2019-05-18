<?php

namespace Drupal\hubspot_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use SevenShores\Hubspot\Http\Client;
use SevenShores\Hubspot\Resources\OAuth2;

/**
 * Configure HubSpot API settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hubspot_api_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'hubspot_api.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hubspot_api.settings');

    $form['access_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#description'   => $this->t('Generate a <a href="https://app.hubspot.com/keys/get" target="_blank">new key</a>.
        Make sure to clear Drupal cache after you change API Key.'),
      '#default_value' => $config->get('access_key'),
    );

    // Status.
    $title = t('OAuth Connection Status');
    $status = $this->oauthIsConnected() ? t('Connected') : t('Disconnected');
    $color = $this->oauthIsConnected() ? 'green' : 'red';
    $form['status_report'] = [
      '#markup' => '<strong>' . $title . ': </strong><span style="color:' . $color . '">' . $status . '</span>',
    ];

    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#required' => TRUE,
      '#default_value' => $config->get('client_id'),
    ];
    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client secret'),
      '#required' => TRUE,
      '#default_value' => $config->get('client_secret'),
    ];
    $form['oauth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('OAuth connection'),
    ];
    $form['oauth']['tokens'] = [
      '#type' => 'details',
      '#title' => $this->t('Tokens'),
      '#open' => TRUE,
      '#access' => (bool) $config->get('access_token') && $config->get('refresh_token'),
    ];
    $form['oauth']['tokens']['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access token'),
      '#required' => FALSE,
      '#default_value' => $config->get('access_token'),
      '#attributes' => ['readonly' => 'readonly'],
    ];
    $form['oauth']['tokens']['refresh_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Refresh Token'),
      '#required' => FALSE,
      '#default_value' => $config->get('refresh_token'),
      '#attributes' => ['readonly' => 'readonly'],
    ];
    $form['oauth']['button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Authorize'),
      '#submit' => ['::submitForm', '::oauthAuthorizeSubmit'],
      '#access' => (bool) !$config->get('access_token'),
    ];
    $form['oauth']['disconnect'] = [
      '#type' => 'submit',
      '#value' => $this->t('Disconnect'),
      '#limit_validation_errors' => [],
      '#submit' => ['::oauthDisconnectSubmit'],
      '#access' => (bool) $config->get('access_token'),
    ];

    $form['help'] = [
      '#type' => 'details',
      '#title' => $this->t('Help'),
      '#open' => FALSE,
    ];
    $form['help']['list'] = [
      '#theme' => 'item_list',
      '#type' => 'ol',
      '#items' => [
        $this->t('Log in to <a href="@url" target="_blank">Hubspot</a> developer portal.', ['@url' => 'https://developers.hubspot.com/']),
        $this->t('<a href="@url" target="_blank">Create an app</a>.', ['@url' => 'https://developers.hubspot.com/docs/faq/how-do-i-create-an-app-in-hubspot']),
        $this->t('Get the Client ID and secret and enter the info here.'),
        $this->t('Click authorize and follow the prompts to connect your app.'),
        $this->t('View the <a href="@url" target="_blank">documentation here</a> for more info.', ['@url' => 'https://developers.hubspot.com/docs/overview']),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit authorize callback to Hubspot.
   */
  public function oauthAuthorizeSubmit(array &$form, FormStateInterface $form_state) {
    $client_id = $this->config('hubspot_api.settings')->get('client_id');
    $client = new Client(['key' => $this->config('hubspot_api.settings')->get('client_secret')]);
    $oauth = new OAuth2($client);
    $uri = $oauth->getAuthUrl(
      $client_id,
      Url::fromRoute('hubspot_api.oauth_redirect', [], ['absolute' => TRUE])->toString(),
      ['contacts']
    );

    $url = Url::fromuri($uri, ['external' => TRUE]);

    $form_state->setResponse(new TrustedRedirectResponse($url->toString()));
  }

  /**
   * Submit disconnect callback to Hubspot.
   */
  public function oauthDisconnectSubmit() {
    $this->config('hubspot_api.settings')->delete();
  }

  /**
   * Submit disconnect callback to Hubspot.
   */
  public function oauthIsConnected() {
    $config = $this->config('hubspot_api.settings');
    return (bool) $config->get('access_token')
      && $config->get('refresh_token')
      && $config->get('client_id')
      && $config->get('client_secret');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('hubspot_api.settings')
      ->set('access_key', $form_state->getValue('access_key'))
      ->set('access_token', $form_state->getValue('access_token'))
      ->set('refresh_token', $form_state->getValue('refresh_token'))
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('client_secret', $form_state->getValue('client_secret'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
