<?php

namespace Drupal\psn_public_trophies\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\psn_public_trophies\Component\DrupalPSNPublicTrophies;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use PSN\AuthException;

/**
 * Edit config variable form.
 */
class PSNPublicTrophiesSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'psn_public_trophies_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'psn_public_trophies.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $drupalPSNPublicTrophies = new DrupalPSNPublicTrophies();

    $config = $this->config('psn_public_trophies.settings');

    $form['psn_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PSN ID'),
      '#description' => $this->t('PSN ID from Playstation'),
      '#default_value' => $config->get('psn_id'),
    ];
    $form['psn_password'] = [
      '#type' => 'password',
      '#title' => $this->t('PSN Password'),
      '#default_value' => $config->get('psn_password'),
      '#attributes' => ['value' => $config->get('psn_password')],
    ];

    $psn_account_token = $drupalPSNPublicTrophies->token;
    if (!$psn_account_token) {
      $form['psn_ticket_uuid_url'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Ticket UUID URL'),
        '#maxlength' => 3000,
        '#description' => $this->t('Click Get Ticket UUID link to login and copy the url and paste it here. DO NOT ENTER THE CODE ON THE PLAYSTATION VERIFICATION FORM.'),
      ];
      $form['psn_get_ticket_uuid'] = [
        '#type' => 'link',
        '#title' => $this->t('Get Ticket UUID'),
        '#url' => Url::fromUri($drupalPSNPublicTrophies->getAuthorizationUrl()),
        '#attributes' => ['target' => '_blank'],
      ];
      $form['psn_verification_code'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Verification Code'),
        '#description' => $this->t('Enter the verification code sent on your mobile device here. DO NOT ENTER THE CODE ON THE PLAYSTATION VERIFICATION FORM.'),
      ];

      $form['psn_connect'] = [
        '#type' => 'submit',
        '#value' => $this->t('Connect'),
        '#submit' => ['::psnConnectSubmit'],
      ];
    }
    else {
      $form['psn_account_token'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Access Token'),
        '#default_value' => Json::encode($psn_account_token),
      ];

      $form['psn_refresh_token'] = [
        '#type' => 'submit',
        '#value' => $this->t('Refresh Token'),
        '#submit' => ['::psnRefreshTokenSubmit'],
      ];

      $form['psn_disconnect'] = [
        '#type' => 'submit',
        '#value' => $this->t('Disconnect'),
        '#submit' => ['::psnDisconnectSubmit'],
      ];

      $form['psn_get_user'] = [
        '#type' => 'submit',
        '#value' => $this->t('Get Data'),
        '#submit' => ['::psnGetUserSubmit'],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Method psnDisconnectSubmit().
   */
  public function psnDisconnectSubmit(array &$form, FormStateInterface $form_state) {
    $drupalPSNPublicTrophies = new DrupalPSNPublicTrophies();
    $drupalPSNPublicTrophies->disconnect();
  }

  /**
   * Method psnGetUserSubmit().
   */
  public function psnGetUserSubmit(array &$form, FormStateInterface $form_state) {
    $drupalPSNPublicTrophies = new DrupalPSNPublicTrophies();
    try {
      $trophy = $drupalPSNPublicTrophies->getTrophy();
      $user = $drupalPSNPublicTrophies->getUser();
      $friends = $drupalPSNPublicTrophies->getFriends();

      $messenger = \Drupal::messenger();
      $messenger->addMessage('Me: ' . Json::encode($drupalPSNPublicTrophies->getUserMe()));
      $messenger->addMessage('My Friends: ' . Json::encode($friends->MyFriends()));
      $messenger->addMessage('My Trophies: ' . Json::encode($drupalPSNPublicTrophies->getMyTrophies()));
    }
    catch (\Exception $e) {
      throw $e;
    }
  }

  /**
   * Method psnRefreshTokenSubmit().
   */
  public function psnRefreshTokenSubmit(array &$form, FormStateInterface $form_state) {
    $drupalPSNPublicTrophies = new DrupalPSNPublicTrophies();
    $drupalPSNPublicTrophies->refreshToken();
  }

  /**
   * Method psnConnectSubmit()
   */
  public function psnConnectSubmit(array &$form, FormStateInterface $form_state) {
    $drupalPSNPublicTrophies = new DrupalPSNPublicTrophies();
    $config = $this->config('psn_public_trophies.settings');

    if ($psn_id = $config->get('psn_id') && $psn_password = $config->get('psn_password')) {
      try {
        $psn_account_token = $drupalPSNPublicTrophies->connect($psn_id, $psn_password, $form_state->getValue('psn_ticket_uuid_url'), $form_state->getValue('psn_verification_code'));
      }
      catch (AuthException $e) {
        header("Content-Type: application/json");
        die($e->GetError());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('psn_public_trophies.settings')
      ->set('psn_id', $values['psn_id']);
    if ($values['psn_password']) {
      $this->config('psn_public_trophies.settings')
        ->set('psn_password', $values['psn_password']);
    }

    $drupalPSNPublicTrophies = new DrupalPSNPublicTrophies();
    $drupalPSNPublicTrophies->disconnect();

    parent::submitForm($form, $form_state);
  }

}
