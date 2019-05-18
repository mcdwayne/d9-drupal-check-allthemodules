<?php

namespace Drupal\shib_auth\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AdvancedSettings.
 *
 * @package Drupal\shib_auth\Form
 */
class AdvancedSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'shib_auth.advancedsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'advanced_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('shib_auth.advancedsettings');

    // $form['strict_shibboleth_session_checking'] = array(
    //      '#type' => 'details',
    //      '#title' => $this->t('Strict Shibboleth Session Checking'),
    //      '#open' => 'open',
    //    );
    //    $form['strict_shibboleth_session_checking']['Destroy Session'] = [
    //      '#type' => 'checkbox',
    //      '#title' => $this->t('Destroy Drupal session when the Shibboleth session expires.'),
    //      '#default_value' => $config->get('Destroy Session'),
    //    ];
    //    $form['terms_of_use_settings'] = array(
    //      '#type' => 'details',
    //      '#title' => $this->t('Terms of Use Settings'),
    //      '#open' => 'open',
    //    );
    //    $form['terms_of_use_settings']['force_terms_of_use'] = [
    //      '#type' => 'checkbox',
    //      '#title' => $this->t('Force users to accept Terms of Use'),
    //      '#default_value' => $config->get('force_terms_of_use'),
    //    ];
    //    $form['terms_of_use_settings']['url_of_document'] = [
    //      '#type' => 'textfield',
    //      '#title' => $this->t('URL of the document'),
    //      '#description' => $this->t('Please refence local content with e.g. &quot;node/1&quot;, or use an external link.'),
    //      '#maxlength' => 128,
    //      '#size' => 64,
    //      '#default_value' => $config->get('url_of_document'),
    //    ];
    //    $form['terms_of_use_settings']['document_version'] = [
    //      '#type' => 'textfield',
    //      '#title' => $this->t('Document version'),
    //      '#maxlength' => 64,
    //      '#size' => 10,
    //      '#default_value' => $config->get('document_version'),
    //    ];.
    $form['login_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Login Settings'),
      '#open' => 'open',
    ];
    $form['login_settings']['url_redirect_login'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL to redirect to after login'),
      '#description' => $this->t('The URL can be absolute or relative to the server base url. The relative paths will be automatically extended with the site base URL. If this value is empty, then the user will be redirected to the originally requested page.'),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $config->get('url_redirect_login'),
    ];

    $form['logout_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Logout Settings'),
      '#open' => 'open',
    ];
    $form['logout_settings']['url_redirect_logout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL to redirect to after logout'),
      '#description' => $this->t('The URL can be absolute or relative to the server base url. The relative paths will be automatically extended with the site base URL. If you are using SLO, this setting is probably useless (depending on the IdP)'),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $config->get('url_redirect_logout'),
    ];
    $form['logout_settings']['logout_error_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Error Page Message'),
      '#default_value' => $config->get('logout_error_message'),
      '#description' => $this->t('Error message displayed to the user (if an error occurs).'),

    ];

    // $form['advanced_saml2_settings'] = array(
    //      '#type' => 'details',
    //      '#title' => $this->t('Advanced SAML2 Settings'),
    //      '#open' => 'open',
    //    );
    //    $form['advanced_saml2_settings']['enable_passive_authentication'] = [
    //      '#type' => 'checkbox',
    //      '#title' => $this->t('Enable passive authentication'),
    //      '#description' => $this->t('Enable passive authentication'),
    //      '#default_value' => $config->get('enable_passive_authentication'),
    //    ];
    //    $form['advanced_saml2_settings']['enable_forced_authentication'] = [
    //      '#type' => 'checkbox',
    //      '#title' => $this->t('Enable forced authentication'),
    //      '#description' => $this->t('Enable forced authentication'),
    //      '#default_value' => $config->get('enable_forced_authentication'),
    //    ];.
    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('shib_auth.advancedsettings')
    // ->set('Destroy Session', $form_state->getValue('Destroy Session'))
    //      ->set('force_terms_of_use', $form_state->getValue('force_terms_of_use'))
    //      ->set('url_of_document', $form_state->getValue('url_of_document'))
    //      ->set('document_version', $form_state->getValue('document_version'))
      ->set('url_redirect_logout', $form_state->getValue('url_redirect_logout'))
      ->set('logout_error_message', $form_state->getValue('logout_error_message'))
      ->set('url_redirect_login', $form_state->getValue('url_redirect_login'))
    // ->set('enable_passive_authentication', $form_state->getValue('enable_passive_authentication'))
    //      ->set('enable_forced_authentication', $form_state->getValue('enable_forced_authentication'))
      ->save();

    // Invalidate the cache for the Shib login block.
    \Drupal::service('cache_tags.invalidator')->invalidateTags(['shibboleth_login_block']);

  }

}
