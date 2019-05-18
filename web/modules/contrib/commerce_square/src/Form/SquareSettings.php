<?php

namespace Drupal\commerce_square\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;

class SquareSettings extends ConfigFormBase {

  protected $permissionScope = [
    'MERCHANT_PROFILE_READ',
    'PAYMENTS_READ',
    'PAYMENTS_WRITE',
    'CUSTOMERS_READ',
    'CUSTOMERS_WRITE',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_square.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_square_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('commerce_square.settings');

    $code = $this->getRequest()->query->get('code');
    if (!empty($code) && !empty($config->get('production_app_id')) && !empty($config->get('app_secret'))) {
      $client = \Drupal::httpClient();
      // We can send this request only once to square.
      $response = $client->post('https://connect.squareup.com/oauth2/token', [
        'json' => [
          'client_id' => $config->get('production_app_id'),
          'client_secret' => $config->get('app_secret'),
          'code' => $code,
        ],
      ]);
      $response_body = Json::decode($response->getBody());
      if (!empty($response_body['access_token'])) {
        $state = \Drupal::state();
        $state->set('commerce_square.production_access_token', $response_body['access_token']);
        $state->set('commerce_square.production_access_token_expiry', strtotime($response_body['expires_at']));
        drupal_set_message($this->t('Your Drupal Commerce store and Square have been successfully connected.'));
      }
    }
    else {
      drupal_set_message($this->t('After clicking save you will be redirected to Square to sign in and connect your Drupal Commerce store.'), 'warning');
    }

    $form['oauth'] = [
      '#type' => 'fieldset',
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#title' => $this->t('OAuth'),
    ];
    $form['oauth']['app_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application Secret'),
      '#default_value' => $config->get('app_secret'),
      '#description' => $this->t('You can get this by selecting your app <a href="https://connect.squareup.com/apps">here</a> and clicking on the OAuth tab.'),
      '#required' => TRUE,
    ];
    $form['oauth']['redirect_url'] = [
      '#type' => 'item',
      '#title' => $this->t('Redirect URL'),
      '#markup' => Url::fromRoute('commerce_square.oauth.obtain', [], ['absolute' => TRUE])->toString(),
      '#description' => $this->t('Copy this URL and use it for the redirect URL field in your app OAuth settings.'),
    ];

    $form['credentials'] = [
      '#type' => 'fieldset',
      '#description' => $this->t('You can get these by selecting your app <a href="https://connect.squareup.com/apps">here</a>.'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#title' => $this->t('Credentials'),
    ];
    $form['credentials']['app_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application Name'),
      '#default_value' => $config->get('app_name'),
      '#required' => TRUE,
    ];

    $form['credentials']['production_app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application ID'),
      '#default_value' => $config->get('production_app_id'),
      '#required' => TRUE,
    ];

    $form['sandbox'] = [
      '#type' => 'fieldset',
      '#description' => $this->t('You can get these by selecting your app <a href="https://connect.squareup.com/apps">here</a>.'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#title' => $this->t('Sandbox'),
    ];
    $form['sandbox']['sandbox_app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sandbox Application ID'),
      '#default_value' => $config->get('sandbox_app_id'),
      '#required' => TRUE,
    ];
    $form['sandbox']['sandbox_access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sandbox Access Token'),
      '#default_value' => $config->get('sandbox_access_token'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('commerce_square.settings');
    $config
      ->set('app_name', $form_state->getValue('app_name'))
      ->set('app_secret', $form_state->getValue('app_secret'))
      ->set('sandbox_app_id', $form_state->getValue('sandbox_app_id'))
      ->set('sandbox_access_token', $form_state->getValue('sandbox_access_token'))
      ->set('production_app_id', $form_state->getValue('production_app_id'));
    $config->save();

    $options = [
      'query' => [
        'client_id' => $config->get('production_app_id'),
        'state' => \Drupal::csrfToken()->get(),
        'scope' => implode(' ', $this->permissionScope),
      ],
    ];
    $url = Url::fromUri('https://connect.squareup.com/oauth2/authorize', $options);
    $form_state->setResponse(new TrustedRedirectResponse($url->toString()));
    parent::submitForm($form, $form_state);
  }

}
