<?php

namespace Drupal\social_post_facebook\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for Social Post Facebook.
 */
class FacebookPostSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['social_post_facebook.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_post_facebook.form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_post_facebook.settings');
    $current_request = $this->getRequest();

    $form['fb_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Facebook App settings'),
      '#open' => TRUE,
      '#description' => $this->t('You need to first create a Facebook App at <a href="@facebook-dev">@facebook-dev</a>', ['@facebook-dev' => 'https://developers.facebook.com/apps']),
    ];

    $form['fb_settings']['app_id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Application ID'),
      '#default_value' => $config->get('app_id'),
      '#description' => $this->t('Copy the App ID of your Facebook App here. This value can be found from your App Dashboard.'),
    ];

    $form['fb_settings']['app_secret'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('App Secret'),
      '#default_value' => $config->get('app_secret'),
      '#description' => $this->t('Copy the App Secret of your Facebook App here. This value can be found from your App Dashboard.'),
    ];

    $form['fb_settings']['graph_version'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 'any',
      '#required' => TRUE,
      '#title' => $this->t('Facebook Graph API version'),
      '#default_value' => $config->get('graph_version'),
      '#description' => $this->t('Copy the API Version of your Facebook App here. This value can be found from your App Dashboard. More information on API versions can be found at <a href="@facebook-changelog">Facebook Platform Changelog</a>', ['@facebook-changelog' => 'https://developers.facebook.com/docs/apps/changelog']),
    ];

    $form['fb_settings']['oauth_redirect_url'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('Valid OAuth redirect URIs'),
      '#description' => $this->t('Copy this value to <em>Valid OAuth redirect URIs</em> field of your Facebook App settings.'),
      '#default_value' => $current_request->getSchemeAndHttpHost() . '/user/social-post/facebook/auth',
    ];

    $form['fb_settings']['app_domains'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('App Domains'),
      '#description' => $this->t('Copy this value to <em>App Domains</em> field of your Facebook App settings.'),
      '#default_value' => $current_request->getHost(),
    ];

    $form['fb_settings']['site_url'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('Site URL'),
      '#description' => $this->t('Copy this value to <em>Site URL</em> field of your Facebook App settings.'),
      '#default_value' => $current_request->getSchemeAndHttpHost(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('social_post_facebook.settings')
      ->set('app_id', $values['app_id'])
      ->set('app_secret', $values['app_secret'])
      ->set('graph_version', $values['graph_version'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
